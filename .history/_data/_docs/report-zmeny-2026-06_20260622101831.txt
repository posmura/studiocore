# Report úprav a rozšíření

Rezervační systém Studio Core  
Období úprav: červen 2026

V rámci posledních úprav byl rezervační systém rozšířen o nové funkce, zpřesněny administrátorské nástroje a doplněna ochrana proti chybovým nebo nebezpečným operacím.

## Hlavní novinka: náhradníci lekcí

Byla doplněna kompletní podpora náhradníků u plně obsazených lekcí. Systém nyní rozlišuje běžného účastníka a náhradníka.

Náhradníci se nezapočítávají do obsazenosti lekce a administrace zobrazuje jejich pořadí podle času přihlášení. Díky tomu je dobře vidět, kdo je první, druhý nebo další v pořadí.

Klient vidí, že je veden jako náhradník. Administrátor a lektor vidí náhradníky odděleně a mohou s nimi pracovat bez ovlivnění skutečné kapacity lekce.

Zrušení náhradníka bylo upraveno tak, aby neměnilo obsazenost lekce a nevracelo kredit tam, kde se kredit za náhradnickou registraci neodečítal.

## Rozvrh a registrace na lekce

Bylo upraveno rušení registrací tak, aby správně fungovalo u běžných účastníků i u náhradníků.

V rozvrhu byla rozlišena tlačítka pro vlastní registraci klienta a pro správu registrací administrátorem nebo lektorem. Ovládání je tak přehlednější hlavně pro uživatele, který má současně administrátorskou roli a vlastní klientskou registraci na lekci.

Byla doplněna zřetelnější informace o stavu registrace klienta, včetně případu, kdy je klient veden jako náhradník.

## Administrace lekcí

Byla upravena informační část lekce nad seznamem účastníků. Informace jsou přehlednější a sjednocené se vzhledem šablony.

Text „NEÚČAST“ byl nahrazen srozumitelnějším textem „ZRUŠENÍ PRO NEÚČAST“.

Byly doplněny přesnější popisy a logování u akcí nad registracemi, aby bylo jasné, jaký klient, jaká lekce a jaká akce byla provedena.

## Uživatelský profil

V uživatelské šabloně byl upraven přehled registrací tak, aby měl pevnou výšku a obsah byl scrollovatelný. Stránka je díky tomu kratší a přehlednější.

Stejná úprava byla provedena pro přehled nákupů.

Pro klientskou roli bylo upraveno rozmístění karet tak, aby byl přehled registrací zobrazen ve vhodnějším sloupci. Pro administrátora a lektora zůstalo zachováno pracovní rozložení.

## Prodej a permanentky

Byla posílena správa prodeje permanentek. Systém nyní kontroluje, zda lze prodej bezpečně smazat, a blokuje odstranění tam, kde by smazání poškodilo historii registrací nebo kreditů.

Při prodeji se kontroluje aktivní permanentka, platnost a případný záporný kredit klienta. Pokud by nový prodej vedl k neplatnému stavu, systém operaci zastaví a zobrazí srozumitelnou chybovou zprávu.

Správa permanentek byla doplněna o validace a ochrany. Permanentku, která už byla použita v prodeji, nelze smazat. V takovém případě je vhodné permanentku deaktivovat.

Ceník zobrazuje pouze aktivní a platné permanentky.

## Správa aktivit

Administrace aktivit byla zabezpečena proti nechtěnému smazání používaných aktivit.

Aktivitu nelze odstranit, pokud je navázaná na lekce, registrace, prodeje, permanentky nebo nenulové kredity.

Formulář aktivity byl doplněn o validace: kontroluje délku názvu, číselné hodnoty, minimální a maximální počet vstupů a logický vztah mezi minimální a maximální kapacitou.

## Přihlášení, registrace a obnova hesla

Byla posílena bezpečnost přihlášení. Systém nyní lépe pracuje s opakovanými neúspěšnými pokusy a umí je dočasně blokovat.

Obnova hesla byla rozšířena o PIN zaslaný SMS. PIN má omezenou platnost, omezený počet pokusů a po úspěšné změně hesla se zneplatní.

Byla doplněna silnější validace hesel, telefonních čísel a registračních údajů.

## Eventlog a auditní záznamy

Zápisy do eventlogu byly upraveny tak, aby neobsahovaly pouze ID hodnoty, ale i čitelné názvy a jména. V logu je tak uvedeno například jméno klienta, název lekce, název aktivity nebo název permanentky.

Byla rozšířena databázová kapacita sloupce pro popis akce v eventlogu, aby delší auditní zprávy nezpůsobovaly chybu při ukládání.

## SMS a chybové stavy

Bylo ošetřeno odesílání SMS na nevalidní telefonní čísla. Místo technické výjimky systém zobrazí uživatelskou chybovou zprávu a formulář zůstane použitelný.

Neoprávněný přístup uživatele už nezobrazuje technickou chybu 403, ale běžnou chybovou zprávu v aplikaci.

## Technické čištění a stabilita

Byly odstraněny nepoužívané metody, zpřehledněny části kódu a doplněny kontroly tam, kde mohlo dojít k neplatnému nebo nejednoznačnému stavu.

Součástí úprav byly také databázové migrace pro náhradníky, úpravy triggerů pro správné počítání obsazenosti lekcí a kontrolní SQL postupy pro nasazení.

## Shrnutí přínosu

Úpravy přinesly hlavně:

- přehlednější práci s lekcemi a náhradníky,
- bezpečnější prodej a správu permanentek,
- spolehlivější přihlašování a obnovu hesla,
- lepší chybové hlášky místo technických výjimek,
- čitelnější auditní log,
- menší riziko poškození historických dat při mazání záznamů,
- přehlednější uživatelské a administrátorské rozhraní.
