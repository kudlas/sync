<?php


class IssuesDbService implements IIssuesDbService
{
    use Logger;

    private $db;

    const POSSIBLE_LAYERS = ['DB',
        'BE',
        'FE',];

    const QUERY_ISSUES_BY_RURL = 'select * from redmine.custom_values where customized_type = \'Issue\' and custom_field_id = 64 and `value` in (?)';
    const PROJECT_VALIDATION = 'select id, project_id from redmine.issues where id in (?);';
    const QUERY_REMOVE_ALL_LAYERS = 'delete from custom_values where customized_id = ? and custom_field_id = 75';
    private $issueIds;
    private $validation;

    public function __construct(array $config)
    {
        $this->db = new Dibi\Connection($config);
    }

    public function fetchIssuesByJtbRUrl(array $urls) : void
    {
        $data = $this->db
            ->query(self::QUERY_ISSUES_BY_RURL, $urls)
            ->fetchAll();

        $this->issueIds = [];

        foreach ($data as $row) {
            $url = $row->value;
            $customized_id = $row->customized_id;

            // so we can check if the issue is whitelisted
            $this->validation[$customized_id] = $url;

            // save for the real stuff
            if ($this->rUrlExists($url)) {
                $this->issueIds[$url][] = $customized_id;
            } else {
                $this->issueIds[$url] = [$customized_id];
            }
        }

        $this->validate( array_keys($this->validation) );

        self::getLogger()->info("Issues fetched from db", $this->issueIds);
    }

    /**
     * get issue project and check if its whitelisted
     * if not, kick it out ouf the issueIds
     */
    private function validate($toValidateIssueIds)
    {
        $data = $this->db->query(self::PROJECT_VALIDATION, $toValidateIssueIds)->fetchPairs("id", "project_id");

        foreach ($data as $issueId => $projId) {
            if (!Validator::checkProject($projId)) {

                self::getLogger()->info("Issue $issueId with project_id $projId not on whitelist, skipping");

                // find the issue to kick it out
                $url = $this->validation[$issueId];
                $indexToDelete = array_search($issueId, $this->issueIds[$url], true);

                // kill that rebel scum
                unset($this->issueIds[$url][$indexToDelete]);
                unset($this->validation[$issueId]);
            }
        }
    }

    /**
     * @param $url
     * @return bool
     */
    public function rUrlExists($url): bool
    {
        return array_key_exists($url, $this->issueIds);
    }

    public function getIssueIdsByUrl($url): array
    {
        return $this->issueIds[$url];
    }

    public function add($url, $issueId): void
    {
        $this->issueIds[$url] = [$issueId];
        $this->validation[$issueId] = $url;
    }


    public function setIssueDevLayer($issue_id, array $layers) : void
    {
        // validations + data formating
        $insertData = [];
        array_filter($layers, function ($val) use ($issue_id, &$insertData) {
            if(in_array($val, self::POSSIBLE_LAYERS)) $insertData[] = [  //   these are data needed for custom_values table in db
                "custom_field_id" => 75,
                "customized_id" => $issue_id,
                "customized_type" => "Issue",
                "value" => $val,
            ];
        });
        if (!is_numeric($issue_id)) {
            self::getLogger()->error('Issue ID is not numeric, skipping dev layer update.');
            return;
        }

        // delete current custom values
        $this->db
            ->query(self::QUERY_REMOVE_ALL_LAYERS, $issue_id)
            ->fetchAll();

        // new values
        $this->db->query('INSERT INTO custom_values', ...$insertData);
        self::getLogger()->info('Dev layer updated');
    }

}