<?php

use Nette\Utils\Strings;

class MailDtoFactory
{
    use Logger;

    const SUBJ_PATTERN = '/(?<crap>.*)\[(?<project>[^-]*)\s-\s(?<type>[^\#]*)\s\#(?<id>\d{1,})\]\s(?<stav>\([^\)]*\)\s)?(?<subject>.*)/';
    const DETAILS_PATTERN = "/<li><strong>(?<key>[^\:]*)\:\s?<\/strong>-?\s?(?<val>[^\<]*)/";
    const CONTENT_PATTERN = "/(?<notes>Úkol [\s\S]*)(?<description><h1 style[^>]*>\s*<a href=\"https:\/\/helpdesk.jtfg.com\/issues\/\d*[\s\S]*)<hr style/";
    const CHANGE_AUTHOR_FROM_NOTE = "/aktualizován\suživatelem\s(?<change_autor>.*)\./";
    const COMMENT_PATTERN = '/<ul class="journal details"[^>]*>\s*\<\/ul>\s*(?<comment>[\S\s]*<\/p>)\s*<hr/mU';
    const ATTACHMENT_PATTERN = '/<li><strong>Soubor<\/strong>\s*<a\shref=["\'](?<address>[^"\']*)["\'][^>]*>(?<text>[^<]*)/';

    const COMMA_REPLACEMENT = [", ", "&#44; "];
    const ISSUE_URL_PATTERN = '/Úkol <a href="(.*)[#"]+/U';


    /** @var PhpImap\IncomingMail $mailData */
    public $mailData;
    private $data;

    /** @var MailDto */
    private $dto;

    public function create(PhpImap\IncomingMail $data)
    {
        $this->mailData = $data;

        $this->dto = new MailDto($data);

        (self::getLogger())->info(sprintf("Preparing mail '%s' with id %s", $this->mailData->subject, $this->mailData->id));

        $this->parseSubject();
        $this->parseDetails();
        $this->parseContent();
        $this->parseAttachments();

        return $this->dto;
    }

    private function parseAttachments()
    {
        preg_match(self::ATTACHMENT_PATTERN, $this->mailData->textHtml, $matches);

        if (isset($matches['address'], $matches['text']))
            $this->dto->set(MailDto::FIELD_ATTACHMENT, ['text' => $matches['text'], 'url' => $matches['address']]);
    }

    private function parseSubject(): void
    {
        preg_match(self::SUBJ_PATTERN, $this->mailData->subject, $matches);
        $this->dto->setAssoc($matches);

        $projTi = $this->dto->get(MailDto::FIELD_PROJECT_TITLE);
        $projId = Config::config[Config::SUBJECT_PROJECT_MAP][$projTi];

        if (!$projId) {
            (self::getLogger())->error(sprintf('Unknown project "%s"', $projTi), $matches);
            throw new Exception('Invalid project');
        }

        (self::getLogger())->info(sprintf('Id of project "%s" recognized as %d', $projTi, $projId));

        $this->dto->set(MailDto::FIELD_PROJECT_ID, $projId);
        $this->dto->set(MailDto::FIELD_INCIDENT, $matches[MailDto::FIELD_TYPE] === MailDto::FIELD_INCIDENT);
    }

    private function parseDetails(): void
    {

        preg_match_all(self::DETAILS_PATTERN, $this->mailData->textHtml, $matches);

        foreach ($matches[0] as $index => $detail) {
            $key = Strings::webalize($matches['key'][$index]);
            $value = trim($matches['val'][$index]);

            $this->dto->set($key, $value);
        }

        (self::getLogger())->info('Mail parsed', $matches);
    }

    private function parseContent(): void
    {
        preg_match(self::CONTENT_PATTERN, $this->mailData->textHtml, $matches);
        $this->dto->setAssoc(array_merge($matches, [MailDto::FIELD_JTB_URL => $this->getIssueUrl($matches[MailDto::FIELD_NOTE])]));

        preg_match(self::CHANGE_AUTHOR_FROM_NOTE, $matches[MailDto::FIELD_NOTE], $changeAuthorMatches);
        $this->dto->set(MailDto::FIELD_CHANGE_AUTHOR, $changeAuthorMatches[MailDto::FIELD_CHANGE_AUTHOR]);

        preg_match(self::COMMENT_PATTERN, $matches[MailDto::FIELD_NOTE], $commentMatches);

        if ($commentMatches) {
            $this->dto->set(MailDto::FIELD_COMMENT, $commentMatches[1]);
        }

        // #50786 fix of notes
        preg_match('/Úkol\s<[^>]+>\s*\#\d+[^\.]+\.(?<content>[\s\S]*)/', $commentMatches[1] ?? $matches[MailDto::FIELD_NOTE], $noteMatch);
        $comment = isset($noteMatch[MailDto::FIELD_CONTENT]) ? preg_replace('/<hr[^>]*>/', '', $noteMatch[MailDto::FIELD_CONTENT]) : $commentMatches[1];
        $this->dto->set(MailDto::FIELD_COMMENT, $comment);

        // fix of commas
        $this->dto->set(MailDto::FIELD_COMMENT, $this->fixCommas($this->data[MailDto::FIELD_COMMENT]));
        $this->dto->set(MailDto::FIELD_DESCRIPTION, $this->fixCommas($this->data[MailDto::FIELD_DESCRIPTION]));
    }

    private function fixCommas($text)
    {
        return str_replace(self::COMMA_REPLACEMENT[0], self::COMMA_REPLACEMENT[1], $text);
    }

    private function getIssueUrl(string $content): ?string
    {
        preg_match(self::ISSUE_URL_PATTERN, $content, $matches);
        if (!$matches[1]) {
            self::getLogger()->info('Cannot parse URL from content');
            return null;
        }

        return $matches[1];
    }
}
