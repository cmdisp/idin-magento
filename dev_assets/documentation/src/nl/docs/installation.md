!!! tip "Advies"
    We adviseren de extensie te installeren via Magento Connect. Zo weet u zeker dat de extensie juist wordt geïnstalleerd en alle installatie scripts juist worden uitgevoerd.

## Installatie / update via Magento Connect
1. Plak de extension key `extension-key` in de Magento Connect Manager
2. Klik op Install

## Handmatige installatie / update
1. Download de laatste versie van de extensie
2. Pak de bestanden uit in een lokale map
3. Plaats de mappen `app`, `lib` en `skin` in de root directory van de Magento installatie
4. Leeg indien nodig de Magento cache zodat de extensie de installatie en upgrade scripts uitvoert

## Verwijderen van extensie
Om er voor te zorgen dat de extensie op een nette manier wordt verwijderd, kunt u dit het beste uitvoeren door onderstaande stappen te volgen.

### De-activatie 
1. Ga naar Systeem --> Configuratie --> Services --> CM iDIN --> Algemeen
2. Zet de optie `Actief` op `Nee`

### Verwijderen van bestanden

!!! note "De-activatie"
    Door eerst de stappen voor het de-activeren te volgen worden aangemaakte attributen automatisch uitgeschakeld.

1. Verwijder de volgende mappen en bestanden

`app/code/community/CMGroep/Idin`<br/>
`app/design/adminhtml/base/default/layout/cmgroep_idin.xml`<br/>
`app/design/adminhtml/base/default/template/cm/idin`<br/>
`app/design/frontend/base/default/layout/cmgroep_idin.xml`<br/>
`app/design/frontend/base/default/template/cmgroep/idin`<br/>
`app/etc/modules/CMGroep_Idin.xml`<br/>
`app/locale/en_US/template/email/cmgroep/idin`<br/>
`app/locale/nl_NL/CMGroep_Idin.csv`<br/>
`lib/CMGroep/Idin`<br/>
`skin/adminhtml/base/default/cm/idin`<br/>
`skin/frontend/base/default/cm/idin`

