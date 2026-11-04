@echo off
title ResolveAI - Seed Database
cd /d "%~dp0"
echo Seeding ResolveAI database with departments, faculty, and students...
php backend\database\seed.php
echo.
echo Database successfully seeded!
pause
