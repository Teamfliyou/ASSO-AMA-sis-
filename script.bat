@echo off
setlocal
echo ============================================
echo Synchronisation et Mise a jour GitHub
echo ============================================

:: Configuration utilisateur
git config --global user.name "Teamfliyou"
git config --global user.email "zyadfliyou25@gmail.com"

:: Initialisation si besoin
if not exist .git (
    echo Initialisation du depot...
    git init
)

:: Liaison avec GitHub
git remote add origin https://github.com/Teamfliyou/ASSO-AMA-sis-.git 2>nul || git remote set-url origin https://github.com/Teamfliyou/ASSO-AMA-sis-.git

:: TELECHARGEMENT DE LA LICENCE ET DES FICHIERS DISTANTS
echo Recuperation des fichiers de GitHub (Licence, etc.)...
git pull origin main --rebase --allow-unrelated-histories

:: AJOUT DES MODIFICATIONS LOCALES
echo Preparation des fichiers locaux...
git add .

:: COMMIT
echo Creation du commit...
git commit -m "Mise a jour avec synchronisation licence (%date%)"

:: ENVOI
echo Envoi vers GitHub...
git push -u origin main

echo.
echo ============================================
echo Termine ! Le fichier LICENSE est maintenant sur votre PC.
echo ============================================
pause