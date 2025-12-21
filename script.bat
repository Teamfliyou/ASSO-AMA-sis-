@echo off
setlocal
echo ============================================
echo Mise a jour du depot GitHub : Teamfliyou
echo ============================================

:: Configuration utilisateur
git config --global user.name "Teamfliyou"
git config --global user.email "zyadfliyou25@gmail.com"

:: Initialisation si le dossier .git n'existe pas
if not exist .git (
    echo Initialisation du depot...
    git init
)

:: Ajout des fichiers (respectera le .gitignore)
echo Preparation des fichiers...
git add .

:: Creation du commit avec message de mise a jour
echo Creation du commit...
git commit -m "Mise a jour du projet ASSO-AMA-sis- (%date% %time%)"

:: Configuration de la branche principale
git branch -M main

:: Liaison avec GitHub
echo Verification de la liaison distante...
git remote add origin https://github.com/Teamfliyou/ASSO-AMA-sis-.git 2>nul || git remote set-url origin https://github.com/Teamfliyou/ASSO-AMA-sis-.git

:: Envoi vers GitHub
echo Envoi des modifications...
git push -u origin main

echo.
echo ============================================
echo Operation terminee avec succes !
echo ============================================
pause