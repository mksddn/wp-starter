# импортируем переменные из .env
if [ -f .env ]; then
  export $(echo $(cat .env | sed 's/#.*//g'| xargs) | envsubst)
else
  echo "${RED}Looks like you don't have .ENV file!${NC}"
  exit
fi

rm -rf vendor composer.lock core
rm -rf content/plugins content/uploads content/upgrade content/debug.log
rm -rf content/themes/${THEME_SLUG}/acf-json/* content/themes/${THEME_SLUG}/style.css