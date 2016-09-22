

set TIMESTAMP=%DATE:~0,2%%DATE:~3,2%%DATE:~6,4%.%TIME:~0,2%%TIME:~3,2%

REM Export all databases into file C:\backups\pos.[year][month][day].sql

"C:\xampp\mysql\bin\mysqldump.exe" --databases rsv --result-file="C:\backups\pos.%TIMESTAMP%.sql" --user=root 

REM Change working directory to the location of the DB dump file.
C:
CD \backups\

REM Compress DB dump file into CAB file (use "EXPAND file.cab" to decompress).
MAKECAB "pos.%TIMESTAMP%.sql" "pos.%TIMESTAMP%.sql.cab"

REM Delete uncompressed DB dump file.
DEL /q /f "pos.%TIMESTAMP%.sql"
