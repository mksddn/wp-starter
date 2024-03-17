export REPOSITORY_NAME=$(basename `git rev-parse --show-toplevel`)

# импортируем переменные из .env
if [ -f site.env ]; then
  export $(echo $(cat site.env | sed 's/#.*//g'| xargs) | envsubst)
else
  echo "${RED}Looks like you don't have SITE.ENV file!${NC}"
  exit
fi

if [ "$1" == "env" ]; then
    cp .env.example .env
    exit

elif [ "$1" == "install" ]; then
    mv wp-content/themes/* wp-content/themes/${THEME_SLUG}
    touch wp-content/themes/${THEME_SLUG}/style.css
    echo "/**
    * Theme Name: ${THEME_SLUG}
    */" | cat - wp-content/themes/${THEME_SLUG}/style.css > temp && mv temp wp-content/themes/${THEME_SLUG}/style.css
    docker-compose up -d
    docker-compose exec -T wp-cli sh < install.sh
    exit
    
elif [ "$1" == "dbexport" ]; then
    docker-compose exec -T wp-cli wp db export dbdump.sql --allow-root
    docker cp wp-cli://var/www/html/dbdump.sql .
    docker exec wp-cli rm -rf dbdump.sql
    exit

elif [ "$1" == "clean" ]; then
    docker compose stop
    docker compose rm
    rm -rf wp-content/plugins
    rm -rf wp-content/uploads
    rm -rf wp-content/upgrade
    rm -rf wp-content/themes/${THEME_SLUG}/acf-json/* wp-content/themes/${THEME_SLUG}/style.css
    exit

fi