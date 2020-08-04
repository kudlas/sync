<?php


class Validator
{
    use Logger;

    static function checkProject(int $projectId): bool {

        $whitelist = Config::config[Config::PROJECT_WHITELIST];
        $isWhitelisted = in_array($projectId, $whitelist);

        if(!$isWhitelisted) {
            self::getLogger()->error('Issue project is not whitelisted, skipping.', [$projectId]);
        }

        return $isWhitelisted;

    }

/*    static function filterIssues($issues) {
       return array_filter($issues['issues'], [__CLASS__, 'checkIssue']);
    }*/

    static function formatAsHtml(string $note): string
    {
        // zbavíme odřádkování
        $ret = trim(preg_replace('/\s\s+/', ' ', $note));

        // fix js
        $regex = '/\$\([\'"](?<showSelector>\#collapse-([^-]+)-show)\,\s(?<hideSelector>#collapse-([^-]+)-hide)[\'"]\).(?<method>\w+)\(\)\;/';

        preg_match_all($regex, $ret, $matches, PREG_SET_ORDER);

        foreach ($matches as $block) {
            $js = '';
            foreach ([$block['showSelector'], $block['hideSelector']] as $selector) {
                $js .= sprintf('$(\'%s\').%s(); ', $selector, $block["method"]);
            }
            $ret = str_replace($block[0], $js, $ret);
        }

        // aby se zpracovalo jako html
        return ' {{html( ' . $ret . ' )}}';
    }

}
