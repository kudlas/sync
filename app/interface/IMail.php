<?php

interface IMail
{
    const FIELD_CRAP = "crap";
    const FIELD_TYPE = "type";
    const FIELD_ID = "id";
    const FIELD_SUBJECT = "subject";
    const FIELD_PROJECT_TITLE = "project";
    const FIELD_PROJECT_ID = "project_id";
    const FIELD_ASIGNEE_NAME = "prirazeno";
    const FIELD_NOTE = "notes";
    const FIELD_DESCRIPTION = "description";
    const FIELD_JTB_URL = "jtb-redmine";
    const FIELD_CHANGE_AUTHOR = "change_autor";

    const FIELD_INCIDENT = 'Incident';
    const FIELD_ATTACHMENT = 'attachment';
    const FIELD_COMMENT = 'comment';
    const FIELD_CONTENT = 'content';

    // generated
    const FIELD_AUTOR = "autor";
    const FIELD_STAV = "stav";
    const FIELD_PRIORITA = "priorita";
    const FIELD_PRIRAZENO = "prirazeno";
    const FIELD_KATEGORIE = "kategorie";
    const FIELD_CILOVA_VERZE = "cilova-verze";
    const FIELD_KRITICNOST = "kriticnost";
    const FIELD_NEXT_STEP = "next-step";
    const FIELD_TYP_POZADAVKU = "typ-pozadavku";
    const FIELD_TYP_POZADAVKU_JINY = "typ-pozadavku-jiny";
    const FIELD_DUVOD_POZADAVKU = "duvod-pozadavku";
    const FIELD_FUNKCIONALITA = "funkcionalita";
    const FIELD_ALGORITMY = "algoritmy";
    const FIELD_OVLADANI = "ovladani";
    const FIELD_UCTOVANI_POPLATKY = "uctovani-poplatky";
    const FIELD_DOKLADY = "doklady";
    const FIELD_REPORTING = "reporting";
    const FIELD_DWH_DOPAD = "dwh-dopad";
    const FIELD_KAPACITNI_OCEKAVANI = "kapacitni-ocekavani";
    const FIELD_PRISTUPOVA_PRAVA = "pristupova-prava";
    const FIELD_BEZPECNOST = "bezpecnost";
    const FIELD_EXTERNI_SYSTEMY = "externi-systemy";
    const FIELD_JINE = "jine";
    const FIELD_AKTUALNI_TERMIN_UKONCENI = "aktualni-termin-ukonceni";
    const FIELD_PREREKVIZITY = "prerekvizity";
    const FIELD_AKCEPTACNI_KRITERIA = "akceptacni-kriteria";
    const FIELD_SERVICE_DESK_DODAVATEL = "service-desk-dodavatel";
    const FIELD_ZADAL_UZIVATEL = "zadal-uzivatel";
    const FIELD_STANOVISKO_DPO = "stanovisko-dpo";
    const FIELD_PREDBEZNA_ANALYZA_DOPADU = "predbezna-analyza-dopadu";
    const FIELD_CENA = "cena";
    const FIELD_PRACNOST_MD = "pracnost-md";
    const FIELD_SCHVALENO_HBA = "schvaleno-hba";
    const FIELD_SCHVALENO_AA = "schvaleno-aa";
    const FIELD_ANALYZA_RESENI_SCHVALENO_AA = "analyza-reseni-schvaleno-aa";
    const FIELD_ANALYZA_RESENI_SCHVALENO_BA = "analyza-reseni-schvaleno-ba";
    const FIELD_ANALYZA_RESENI_SCHVALENO_HBA = "analyza-reseni-schvaleno-hba";
    const FIELD_PROSTREDI = "prostredi";
    const FIELD_DEV_LAYER = "vyvojova-vrstva";

    public function get($field);

    public function setAssoc($data);

    public function fieldExists($field): bool;

    public function getFields($keys): array;
}
