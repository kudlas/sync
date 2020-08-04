<?php

class Config
{
    // mail stuff
    const MAIL_DATE_FORMAT = 'j F Y';

    // field names of config
    const MAILBOX = "mailbox";
    const PROJECT_WHITELIST = "whitelist";

    const ISSUES_COUNT = "total_count";
    const SUBJECT_PROJECT_MAP = "subject_project";

    const config = [
        self::MAILBOX => [
            'server' => '{ex1.siteone.cz:993/imap/ssl/novalidate-cert}INBOX',
            'user' => 'WORKGROUP\jtbhelpdesk',
            'pass' => 'baker22AIRCRAFTnathan',
        ],
        self::PROJECT_WHITELIST => [
            24,  // J&T
            57,  // J&T - CCP
            66,  // databaze
            34,  // eatlantik
            38,  // ebroker
            392, //J&T - ePortal redesign
            159, // jtb helpdesk
            230, // oms
            219, // "J&T - Pipeline"
            381, // jt td
            25,  // J&T - Weby
            350, // J&T Bank - optimalizace onlinu - jtbank.cz
        ],
        self::SUBJECT_PROJECT_MAP => [
            'incident' => 159,
            'eAtlantik' => 34,
            'eBroker, KUL' => 38,
            'CCP Centrum cenných papírů' => 57,
            'OMS' => 230,
        ],
        self::STATUS_MAP => [
            'Otevřený' => 1, // New
            'Uzavřený' => 5, // Closed
            'Zrušený' => 5, // Closed
            'Pozastaven' => 8, // Waiting
            'Otestováno bez chyby' => 9, // Ready to release
            'Předáno Návrh analýzy/ Řešení' => 12, // Estimate
            'Schválen Návrh analýzy/ Řešení' => 14, // Waiting for Order
            'Otestováno s chybou' => 16, // To Solve
            'Zadáno na vývoj' => 16, // To Solve
            'Dodáno na TEST' => 17, // Ready to Test
            'Nasazeno na produkci' => 18, // Released
        ]
    ];
    const STATUS_MAP = 'status_map';

    const LOG_MAX_AGE = '21 day';

    const PRIORITIES = [
        'Nízká' => 3, // Low
        'Normální' => 4, // Normal
        'Vysoká' => 5, // High
    ];

    public static function getTempPath() { return realpath(__DIR__ . '/../../temp'); }

    static function mapperCF65($envName) {
        $map = [
            'DEV1' => 's1dev',
            'DEV2' => 's1dev2',
            'INTEGR' => 'integr',
            'AKCEPT' => 'akcept',
            'PREPROD' => 'preprod',
            'PROD' => 'prod',
        ];

        return $map[$envName];
    }


    static function getAsNumeric($key) {
        return array_values(static::config[$key]);
    }

    static function getDbCreds() {
       return [
            'driver'   => 'mysqli',
            'host'     => getenv('DB_HOST'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
            'database' => getenv('DB_DATABASE'),
        ];
    }
    static function getRedmineCreds() {
       return [
           'URL' => getenv('REDMINE_URL'),
           'LOGIN' => getenv('REDMINE_LOGIN'),
           'PASSWORD' => getenv('REDMINE_PASSWORD'),
       ];
    }

    static function getApiUrl() {
        $data = Config::getRedmineCreds();
        return sprintf("%s://%s:%s@%s", getenv('REDMINE_PROTOCOL'), $data["LOGIN"], $data["PASSWORD"], $data["URL"] );
    }

    static function getDebug() {
        return getenv('DEBUG') ?? true;
    }
}
