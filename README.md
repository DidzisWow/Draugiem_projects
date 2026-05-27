# Stipendiju Aprēķins

## Prasības
- PHP 8.x
- MySQL
- Composer

## Uzstādīšana
1. Klonē repozitoriju
2. Izpildi `composer install`
3. Importē datubāzi: izpildi SQL no `database.sql`
4. Atjauniniet `db.php` ar saviem datubāzes pieslēguma datiem
5. Startē serveri: `php -S localhost:8000`
6. Atver `http://localhost:8000`

## Lietošana
1. Augšupielādē mācību priekšmetu Excel datni (.xlsx) 1. sadaļā
2. Iestatī semestra datumus un mēneša budžetu 2. sadaļā
3. Pielāgo stipendiju apmērus 3. sadaļā, ja nepieciešams
4. Augšupielādē audzēkņu vērtējumu Excel datni no E-klases 4. sadaļā
5. Nospied "Aprēķināt stipendijas"
6. Aplūko rezultātus, izmanto rūtiņas, lai izslēgtu vērtējumus un pārrēķinātu
7. Noklikšķini uz audzēkņa uzvārda, lai redzētu viņa individuālos vērtējumus
8. Eksportē rezultātus uz Excel, izmantojot eksportēšanas pogu

## Excel Datņu Formāti
- **Priekšmetu datne**: A kolonna = priekšmeta nosaukums, B kolonna = VIMP vai PROF
- **Vērtējumu datne**: Standarta E-klases eksporta formāts

## Datubāzes Struktūra
- `students` — audzēkņu dati (vārds, uzvārds, personas kods, grupa)
- `grades` — vērtējumi (priekšmets, vērtējuma veids, vērtējums, datums)
- `subjects` — mācību priekšmeti un to kategorijas (VIMP/PROF)
- `settings` — semestra datumi un mēneša budžets
- `scholarship_table` — stipendiju apmēru tabula pēc vērtējumu diapazona

## Stipendiju Aprēķina Loģika
- Ja audzēknim ir 2 vai vairāk nesekmīgi priekšmeti — stipendija 0.00 €
- Ja audzēknim ir 1 nesekmīgs priekšmets — stipendija 15.00 €
- VIMP priekšmetā nesekmīgs vērtējums ir zemāks par 4.0
- PROF priekšmetā nesekmīgs vērtējums ir zemāks par 5.0
- Pārējos gadījumos stipendija tiek noteikta pēc vidējā vērtējuma un stipendiju tabulas
- Galīgais vērtējums priekšmetā ir prioritārs pār II semestra vērtējumu

## Tehnoloģijas
- PHP 8.x — backend loģika
- MySQL — datubāze
- PhpSpreadsheet — Excel datņu nolasīšana un eksports
- HTML/CSS/JavaScript — lietotāja saskarne