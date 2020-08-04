<?php

use ActiveResource\ActiveResource;

/**
 * IssueModel::create([
 * "project_id" => 230,
 * "subject" => "this is about...",
 * "description" => "description of the issue",
 * "assigned_to_id" => 534,
 * ])
 */
class RedmineModel
{
    use Logger;

    // keys from api are different from those from db
    CONST KEY_TRANSLATION = [
        "project" => "project_id"
    ];

    private static function respBodyToArray($respBody)
    {
        $Sxml = new SimpleXMLElement($respBody);

        $ret = [];
        foreach ($Sxml as $key => $item) {
            $key = array_key_exists($key, self::KEY_TRANSLATION) ? self::KEY_TRANSLATION[$key] : $key;
            $ret[$key] = self::getValueRecursion($item);
        }

        return $ret;
    }

    private static function getValueRecursion($value)
    {
        if (is_string($value)) return $value;

        $keys = [0, 'id', "@attributes"];
        $arr = (array)$value;

        foreach ($keys as $key) {
            if (array_key_exists($key, $arr)) {
                return self::getValueRecursion($arr[$key]);
            }
        }
    }

    public static function getUserByName(string $name) : ?int {
        $u = new User();
        $u->find(false, array ('name' => $name, 'limit' => 1));

        $id = @RedmineModel::respBodyToArray($u->response_body);
        return empty($id) ? null : $id['user'];
    }

    public static function create(array $fields)
    {
        $i = new Issue($fields);
        $i->save();

        return self::respBodyToArray($i->response_body);
    }

    public static function update($id, $fields): bool
    {
        $i = new Issue();
        $issue = $i->find($id);

        // getting project id to confirm we can change this issue
        try {
            $pId = (array)($issue->project->attributes()[0]);
            $pId = $pId[0];
        } catch (Exception $e) {
            self::getLogger()->error("Cannot verify issue project", [$id]);
            return false;
        }

        // check project if we can update it
        if (!Validator::checkProject($pId)) {
            self::getLogger()->error("Trying to write into project which is not whitelisted!", ["pid" => $pId, "issue id" => $id, "fields" => $fields]);
            return false;
        }

        // write data into issue object
        array_walk($fields, function ($val, $key) use ($issue) {
            $issue->set($key, $val);
        });

        // save it
        $issue->save();

        return true;
    }
}

class Issue extends ActiveResource
{
    public $site = null;
    public $element_name = 'issue';
    public $request_format = 'xml';

    public function __construct($data = array())
    {
        $this->site = Config::getApiUrl();
        parent::__construct($data);
    }
}

class User extends ActiveResource
{
    public $site = null;
    public $element_name = 'user';
    public $request_format = 'xml';

    public function __construct($data = array())
    {
        $this->site = Config::getApiUrl();
        parent::__construct($data);
    }
}