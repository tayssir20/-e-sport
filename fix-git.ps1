# Script pour réparer la branche main (objet manquant + sync avec origin)
# À exécuter dans PowerShell à la racine du projet

Set-Location $PSScriptRoot

Write-Host "1. Récupération des refs depuis origin..." -ForegroundColor Cyan
git fetch origin

Write-Host "`n2. Réinitialisation de main sur origin/main..." -ForegroundColor Cyan
git reset --hard origin/main

Write-Host "`n3. Vérification..." -ForegroundColor Cyan
git status
git log --oneline -3

Write-Host "`nTerminé. Vous pouvez à nouveau travailler et pousser si besoin." -ForegroundColor Green
