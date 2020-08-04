<?php

class Mailbox
{
    use Logger;

    const MAIL_CRITERIA = 'UNSEEN SINCE "%s"';

    private $mailbox;
    private $mails;
    private $jtbR_urls = [];

    public function __construct(...$params)
    {
        $this->mailbox = new PhpImap\Mailbox(...$params);
    }

    public function downloadEmails($customCriteria = null, $debugMailId = null)
    {
        $date = date(Config::MAIL_DATE_FORMAT, strtotime('-1 week'));
        $criteria = $customCriteria ?? sprintf(self::MAIL_CRITERIA, $date);

        $mailsIds = (!$debugMailId) ? $this->mailbox->searchMailbox($criteria, true) : [$debugMailId];

        if (!$mailsIds) {
            (self::getLogger())->info('No new mails found');
            return;
        }

        (self::getLogger())->info('Found these mail ids', $mailsIds);
        $this->parseMails($mailsIds);
    }

    private function parseMails(array $ids)
    {
        array_walk($ids, function ($id) {
            self:
            self::getLogger()->info("Getting mail id " . $id);
            $mailData = $this->mailbox->getMail($id, !Config::getDebug());

            try {
                $mail = (new MailDtoFactory())->create($mailData);
                $this->mails[] = $mail; // wont execute if new mail throws error

                // lets take all bank's redmine urls (unique)
                $this->setJtbUrls($mail->get(MailDto::FIELD_JTB_URL));
            } catch (Exception $e) {
                (self::getLogger())->error('Parsing mail failed', [$e->getMessage()]);
            }
        });
    }

    /**
     * set tasks url (url in jtb redmine), saves only if url does not exists yet in list
     */
    private function setJtbUrls($url)
    {
        $this->jtbR_urls[$url] = null;
    }

    public function getJtbUrls()
    {
        return array_keys($this->jtbR_urls);
    }

    /**
     * @return mixed
     */
    public function getMails(): ?array
    {
        return $this->mails;
    }

}
