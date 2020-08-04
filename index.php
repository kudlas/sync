<?php

error_reporting(-1);
date_default_timezone_set('Europe/Prague');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/autoload.php';

DebugConsole::outputSettings();
DebugConsole::clearLogs();

$loggerFactory = new LoggerFactory();
DebugConsole::output('Log generated at: ' . $loggerFactory->getPath());

$mailbox = new Mailbox(...Config::getAsNumeric(Config::MAILBOX));

// which emails to get in debug mode
$testDate = date(Config::MAIL_DATE_FORMAT, strtotime('29 June 2020'));
$debugMailId = null; //  2864;//2873;2889
DebugConsole::output("Debugger will get emails since " . $testDate);

$mailbox->downloadEmails(Config::getDebug() ? "ON \"$testDate\"" : null, Config::getDebug() ? $debugMailId : null);

// no point in doing anything, if there is no new stuff
if (!$mails = $mailbox->getMails()) {
    ($loggerFactory)->getLogger()->info("no new emails");
    die(PHP_EOL . "no mails" . PHP_EOL);
}

$existingIssues = new IssuesDbService(Config::getDbCreds());
$existingIssues->fetchIssuesByJtbRUrl($mailbox->getJtbUrls());

$redmine = new Redmine($existingIssues, ...$mails); // Beware! it creates new (not found) issues right in constructor
// $redmine->createIssues();
$redmine->updateIssues();

DebugConsole::output('all done' . PHP_EOL);
