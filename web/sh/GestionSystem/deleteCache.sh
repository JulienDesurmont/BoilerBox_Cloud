echo "                  Analyse du cache boiler-box"
visuCache=$(ls -l "${BOILERBOX}/app/cache")
echo "Avant : $visuCache"
echo "                      -----------------"
suppCacheDev=$(rm -rf "${BOILERBOX}/app/cache/dev")
suppCacheProd=$(rm -rf "${BOILERBOX}/app/cache/prod")
visuCache=$(ls -l "${BOILERBOX}/app/cache")
echo "Aprés : $visuCache"
echo ""
echo ""
echo "                  Analyse des logs boiler-box"
visuLogs=$(ls -l "${BOILERBOX}/app/logs")
echo "Avant : $visuLogs"
echo "                      -----------------"
suppLogs=$(rm -rf "${BOILERBOX}/app/logs")
creatLogs=$(mkdir "${BOILERBOX}/app/logs")
defineDroits=$(chmod 777 "${BOILERBOX}/app/logs")
visuLogs=$(ls -l "${BOILERBOX}/app/logs")
echo "Aprés : $visuLogs"
