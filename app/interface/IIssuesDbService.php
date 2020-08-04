<?php


interface IIssuesDbService
{
    public function rUrlExists($url): bool;
    public function getIssueIdsByUrl($url): array;
    public function setIssueDevLayer($issue_id, array $layers): void;
}