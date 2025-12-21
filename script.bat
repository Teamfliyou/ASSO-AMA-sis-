@echo off
echo Configuration de Git pour Teamfliyou...
git config --global user.name "Teamfliyou"
git config --global user.email "zyadfliyou25@gmail.com"

echo Initialisation du depot...
git init

echo Ajout des fichiers...
git add .

echo Creation du commit...
git commit -m "Upload automatique du projet ASSO-AMA-sis-"

echo Configuration de la branche principale...
git branch -M main

echo Liaison avec GitHub...
:: Cette ligne essaie d'ajouter, si ca existe deja, elle modifie l'URL
git remote add origin https://github.com/Teamfliyou/ASSO-AMA-sis-.git 2>nul || git remote set-url origin https://github.com/Teamfliyou/ASSO-AMA-sis-.git

echo Envoi vers GitHub...
git push -u origin main

echo.
echo Operation terminee ! Verifie ton navigateur.
pause