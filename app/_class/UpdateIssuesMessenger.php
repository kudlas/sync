<?php


class UpdateIssuesMessenger
{
    public $issues;

    /** @var MailDtoFactory */
    public $mail;

    public function __construct(array $issueIds, MailDto $mail)
    {
        $this->mail = $mail;
        $this->issues = $issueIds;
    }
}
