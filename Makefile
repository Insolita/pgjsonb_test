init:
	composer install && mkdir app/runtime && chmod -R 0777 app/runtime && mkdir -p _db/pg10 && mkdir -p _db/pg11  \
&& mkdir -p _db/pg12 && mkdir -p _db/pg13 && docker-compose up -d

migrate:
	./yii migrate --interactive=0 --db=db10 && ./yii migrate --interactive=0 --db=db11 && \
./yii migrate --interactive=0 --db=db12  && ./yii migrate --interactive=0 --db=db13

unmigrate:
	./yii migrate/down 1 --interactive=0 --db=db10 && ./yii migrate/down 1 --interactive=0 --db=db11 && \
./yii migrate/down 1 --interactive=0 --db=db12  && ./yii migrate/down 1 --interactive=0 --db=db13

rollback:
	./yii migrate/down 6 --interactive=0 --db=db10 && ./yii migrate/down 6 --interactive=0 --db=db11 && \
./yii migrate/down 6 --interactive=0 --db=db12  && ./yii migrate/down 6 --interactive=0 --db=db13

seed:
	./yii seed --users=2000 --forms=100

seedBig:
	./yii seed --users=10000 --forms=200

bench:
	./yii bench --repeat=10 --delay=500000
