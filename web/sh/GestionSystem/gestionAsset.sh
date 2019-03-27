echo "Installation des Assets"
commandeInstall=$(php "${BOILERBOX}/app/console" asset:install)
echo "Install $commandeInstall"
echo ""
echo ""
echo "Dump des Assets"
commandeDump=$(php "${BOILERBOX}/app/console" asset:dump --env=prod)
echo "Dump : $commandeDump"
