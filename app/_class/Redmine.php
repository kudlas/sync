<?php

class Redmine
{
    use Logger;

    /** @var \MailDto[] */
    private $rawMails;

    // private $iToCreate;
    private $iToUpdate;
    private $client;
    private $issuesService;

    public function __construct(IIssuesDbService $issuesService, IMail ...$rawMails) // array of objects type hint workaround
    {
        $this->issuesService = $issuesService;
        $this->rawMails = $rawMails;

        $creds = Config::getRedmineCreds();

        $this->client = new \Redmine\Client($creds["URL"], $creds["LOGIN"], $creds["PASSWORD"]); // when you do 'use' statement istead of full namespace, it stops working, lol (namespaces).

        $this->filterMails();
    }

    private function filterMails() // update or create
    {
        foreach ($this->rawMails as $mail) {

            $issueTitle = $mail->get(MailDto::FIELD_SUBJECT);
            $issueRurl = $mail->get(MailDto::FIELD_JTB_URL);
            $rUrlExists = $this->issuesService->rUrlExists($issueRurl);

            if (!$rUrlExists) {
                // we are creating right away, otherwise we could introduce duplicate issues
                (self::getLogger())->info("Issue '$issueTitle' not found, lets create it.");
                $newIssue = $this->createIssueFromMail($mail);

                // add to existing issues, to avoid duplicates
                $this->issuesService->add($mail->get(MailDto::FIELD_JTB_URL), $newIssue['id']);

                continue;
            }

            // but for updating, we are saving them for later and doing it all at once elsewhere
            $issueIds = $this->issuesService->getIssueIdsByUrl($issueRurl);
            $this->iToUpdate[] = new UpdateIssuesMessenger($issueIds, $mail);

            (self::getLogger())->info("Issue found to be updated: '$issueTitle', with ids:", [$issueIds]);
        }
    }

    public function createIssueFromMail(IMail $mail)
    {
        self::getLogger()->info('Creating issue', [$mail->get(MailDto::FIELD_SUBJECT)]);
        $issueData = $this->issueDataFromMail($mail);

        //$newIssue = $this->client->issue->create($issueData);
        $newIssue = RedmineModel::create($issueData);

        $logData = $issueData;
        unset($logData[MailDto::FIELD_DESCRIPTION]);

        self::getLogger()->info("Issue created", $issueData);

        return $newIssue;
    }


    private function usernameFromName($name)
    {
        list($lastname, $firstname) = explode(" ", $name);
        return Nette\Utils\Strings::webalize("$firstname.$lastname", '.');
    }

    public function updateIssues()
    {
        if (!$this->iToUpdate) {
            self::getLogger()->info("Nothing to Update");
            return;
        }

        array_map(function (UpdateIssuesMessenger $data) {
            array_map(function ($issue) use ($data) {


                $fields = $this->issueDataFromMail($data->mail);
                self::getLogger()->info('Trying to update issue: ' . $issue . " with mail " . $data->mail->mailData->id);

                DebugConsole::output("mail id: " . $data->mail->mailData->id);

                // updating custom field Dev layer
                $layers = $data->mail->get(MailDto::FIELD_DEV_LAYER);
                if ($layers) {
                    $this->issuesService->setIssueDevLayer($issue, explode(", ", $layers));
                    self::getLogger()->info('Dev layer successfully set to: ' . $layers);
                }

                DebugConsole::output("updating issue " . $issue);

                if (RedmineModel::update($issue, $fields)) {
                    self::getLogger()->info('Issue ' . $issue . ' updated.', [$fields]);
                }
            }, $data->issues);

        }, $this->iToUpdate);
    }

    /**
     * @param MailDtoFactory $issueMail
     * @return mixed
     */
    public function assigneeNameFromMail(IMail $issueMail, $field = MailDto::FIELD_ASIGNEE_NAME): ?int
    {
        $username = $this->usernameFromName($issueMail->get($field));
        self::getLogger()->info('Looking for *' . $username . '* to assign him/her issue: "' . $issueMail->get(MailDto::FIELD_SUBJECT) . '"');
        $userId = @RedmineModel::getUserByName($username);

        self::getLogger()->info('This is what I\'ve found', [$userId]);

        if ($userId === null && $field == MailDto::FIELD_ASIGNEE_NAME) // when we dont have this user in our RM
        {
            self::getLogger()->info('I couldn\'t find him, fallbacking to author.');
            return $this->assigneeNameFromMail($issueMail, 'autor');
        } else {
            self::getLogger()->info('Assigning to user id: ' . $userId . " - $username");
            return $userId;
        }
    }

    /**
     * @param MailDtoFactory $mail
     * @return array
     */
    public function issueDataFromMail(IMail $mail): array
    {
        $issueData = $mail->getFields([
            MailDto::FIELD_PROJECT_ID,
            MailDto::FIELD_SUBJECT,
            MailDto::FIELD_DESCRIPTION
        ]);

        $fields = [
            '@type' => "array",
            'custom_field' => [],
        ];

        $fields['custom_field'][] = [
            'id' => 64,
            'value' => $mail->get(MailDto::FIELD_JTB_URL)
        ];

        $issueData['description'] = Validator::formatAsHtml($issueData['description']);

        if ($this->customFieldExists(MailDto::FIELD_PROSTREDI, $mail)) {
            $fields['custom_field'][] = [
                'id' => 65, // prostredi
                'value' => Config::mapperCF65($mail->get(MailDto::FIELD_PROSTREDI))
            ];
        }

        /*if ($this->customFieldExists(MailDtoFactory::FIELD_PROSTREDI, $mail)) {
            $fields['custom_field'][] = [
                'id' => 75, // Vývojová vrstva JTB
                'value' => [explode(", ", $mail->get(MailDtoFactory::FIELD_DEV_LAYER) )]
            ];
        }*/

        $issueData['custom_fields'] = $fields;

        if ($mail->fieldExists('comment') || $mail->fieldExists('notes')) {

            $latte = new Latte\Engine;
            $latte->setTempDirectory( Config::getTempPath() );

            $html = $latte->renderToString(__DIR__ . '/../templates/note.latte', ['mail' => $mail]);

            $issueData[MailDto::FIELD_NOTE] = Validator::formatAsHtml( $html );
        }

        $issueData['assigned_to_id'] = $this->assigneeNameFromMail($mail);

        $statuses = Config::config[Config::STATUS_MAP];
        $currentStatus = trim($mail->get('stav'));

        if (array_key_exists($currentStatus, $statuses))
            $issueData['status_id'] = $statuses[$currentStatus];

        $issueData['priority_id'] = Config::PRIORITIES[$mail->get('priorita')];

        return $issueData;
    }

    private function customFieldExists($field, MailDto $mail)
    {
        return $mail->fieldExists($field) && $mail->get($field) !== "";
    }

}
