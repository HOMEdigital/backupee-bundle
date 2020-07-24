# backupee-bundle

Adds automated database backups and triggered database backups/imposts to contao 4.

## Installation

```
composer require home/backupee-bundle
```

## Usage

The bundle uses the `initializeSystem` hook to check when the last backup has been taken.
When the last backup is older than 24 hours it will create a new backup.
It will keep 30 backups, older will be deleted.
The backup location is in `/files/dbBackup`, so it is possible to download them from the contao backend.

* To force a new backup call `/backup/now`
* To force a new backup and download call `/backup/download`
* To import a mysql dump call `/import/{fileName}`
