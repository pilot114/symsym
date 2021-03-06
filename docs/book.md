### Что тут

Это избранные выжимки из книги по Symfony от разработчиков https://symfony.com/book  
Текст разбит по главам, как это сделано в оригинале.
> так выделены мои примечания

# Checking your Work Environment

Все достаточно быстро устанавливается по инструкции.  
Также можно установить Symfony CLI, это сильно упростит многие моменты разработки.
- локальный php сервер
- работа с облаком (Platform.sh)
- работа с сертификатами
- проверка на уязвимости
- прокси для доменного имени
- и многое другое

В этом случае, вызов консольных команд тоже оборачивается (для переменных окружения,
выбора подходящей версии php и т.д.), например:

    composer => symfony composer
    ./bin/console => symfony console

> Окружение в системе или в контейнере?  
Удобнее, конечно, все заранее установить в docker, особенно если есть готовый образ, используемый на продакшене.
Однако, в этом случае многие фичи Symfony CLI могут работать неправильно, и создание их аналогов в docker потребует
дополнительной квалификации и времени.
Поэтому лучше сразу выбрать, какой из этих 2 вариантов подойдет лучше и следовать ему.  
Рекомендации по докер (https://symfony.com/doc/current/setup/docker.html)  
Пример окружения https://github.com/dunglas/symfony-docker

# Introducing the Project

Проекты по архитектуре бэкенда можно условно разделять на класические (с шаблонизацией на php),
API и SPA. Причем с точки зрения бэкенда SPA вариант - это то же API, но специфичное для работы с SPA приложением.

> Какой вариант выбрать, чтобы не пришлось переделывать?
Думаю, наиболее прагматично делать универсальное API, способное одинаково хорошо работать с любыми клиентами.
Шаблонизацию на php я делать не рекомендую (кроме, возможно, совсем простой статики вроде писем), т.к. с этим гораздо
органичнее справляются компонентные js фреймворки.

Пример архитектуры проекта (из книги)
![](https://symfony.com/doc/6.0en//the-fast-track/_images/infrastructure.png)
Этот проект можно установить командой

    symfony new --version=6.0-1 --book guestbook

Book Driven Development! По этому проекту можно перемещаться, чтобы отслеживать шаги разработки
и смотреть разницу по шагам

    symfony book:checkout 10.2
    git diff step-10-1...step-10-2

Важной метрикой можно выделить кол-во кода и конфигураций, который нужно написать для реализации проекта.
Вот инструмент, который может помочь в этом (https://github.com/sebastianbergmann/phploc)

# Going from Zero to Production

Любой проект можно начать со страницы "В разработке" и выкатить в продакшн.
Пример ининциализации

    symfony new app --webapp --docker --cloud

webapp - устанавливает набор стандартных компонентов, которые не включены по умолчанию
docker - включает использование docker для сервисов, типа postgres (могут добавляться через flex)
cloud - если хотите развернуть проект на платформе Platform.sh, с хорошей поддержкой symfony проектов (создает .platform/* файлы)

Symfony Flex — это плагин Composer, который подключается к процессу установки.  
Когда он обнаруживает пакет, для которого у него есть рецепт, он выполняет его.

По файловой структуре:
bin - для консольных команд
config - yaml конфигурации. Каждый пакет создает свой файл
public - то, что доступно web серверу напрямую
src - код проекта
var - единственный каталог, который должен быть доступен для записи в рабочей среде

Как задеплоить в Platform.sh?

    symfony cloud:project:create --title="Guestbook" --plan=development
    symfony cloud:deploy
    symfony cloud:url -1
    # удалить:
    cloud:project:delete

Интересный факт - в .gitignore есть строки специального формата, чтобы flex знал что можно удалить вместе с пакетом.

> TODO: тут неплохо было бы расписать список пакетов и мета-пакетов, что от чего зависит

# Troubleshooting Problems

debug, входящий в `webapp`, добавляет отладочную панель внизу страницы.
Другой, более универсальный вариант отладки по логам:

    symfony server:log

APP_ENV переменная отвечает за выбор текущего окружения (по умолчанию есть dev, prod, и test)
Переключить можно просто установкой нового значения

    export APP_ENV=dev

Или изменением в файле .env  
Файл .env фиксируется в репозитории и описывает значения по умолчанию из производства  
Вы можете переопределить эти значения, создав .env.local файл. Никогда не храните секретные
или конфиденциальные значения в этих файлах.

Для настройки ссылок из лога в IDE (и не только), нужно прописать в php.ini

    xdebug.file_link_format = "phpstorm://open?file=%f&line=%l" 

Отладка продакшена (режим readonly)

    symfony cloud:logs --tail
    symfony cloud:ssh

# Creating a Controller

maker, входящий в `webapp`, позволяет генерировать целые блоки проекта.

    symfony console make:controller ConferenceController

Роутинг контролера определяется через аттрибуты, работает инъекция зависимостей.
dump позволяет дампить любые данные - напрямую на странице или в панели, если она есть.

# Setting up a Database

Если проект создан с поддержкой docker и установлен соотвествующий пакет (doctrine/orm),
подключение в БД будет автоматическое. Чтобы при подключении к базе из консоли не указывать вручную переменные окружения,
можно законнектиться через обертку:

    symfony run psql

Создать / восстановить бэкап:

    symfony run pg_dump --data-only > dump.sql
    symfony run psql < dump.sql

В .platform/services.yaml также автоматически прописываються настройки сервиса БД.
В .platform.app.yaml этот сервис прикрепляется через директиву

    relationships:
        database: "database:postgresql"

> TODO: нужна документация по .platform.app.yaml

Чтобы подключиться к контейнеру в производственной среде, нужно создать туннель:

    # коннект
    symfony cloud:tunnel:open
    # пробрасываем переменные среды
    symfony var:expose-from-tunnel
    # вывести переменные среды
    symfony var:export
    # подключаемся к БД
    symfony run psql
    # закрыть туннель
    symfony cloud:tunnel:close

# Describing the Data Structure

Работа с БД целиком управляется библиотекой Doctrine.
Как и любой другой пакет, доктрина имеет специальный конфиг (config/packages/doctrine.yaml) 
Каждый пакет-сервис публикует свои env переменные, со своим префиксом == названию сервиса.

> Важный момент: symfony может подтягивать переменные окружения напрямую из запущенных контейнеров.

Сначала нужно определиться со структурой данных, заданной в виде Entity:

    symfony console make:entity Conference
    symfony console make:entity Comment
    # выполнение той же команды еще раз позволит добавить новые поля
    symfony console make:entity Conference

Результатом генерации будет сущность и репозиторий. С доктриной они связаны только аттрибутами.
Для создания связи нужно в качестве типа поля указать OneToMany / ManyToOne / ManyToMany / OneToOne.
Если вы введете `?` в качестве ответа для типа, вы получите все поддерживаемые типы.

Миграции

    # создать
    symfony console make:migration
    # применить
    symfony console doctrine:migrations:migrate

На производственной БД миграция накатиться автоматически при следующем деплое.

# Setting up an Admin Backend

Компоненты Symfony - основные функции и низкоуровневые абстракции (http, mail, кэш и т.д.)
Пакеты Symfony - функции высокого уровня или интеграция со сторонними библиотеками

Стандартный пакет для админок - EasyAdmin. Можно не указывть полное имя пакета, а использовать псевдоним
(это функционал, добавляемый flex)

    symfony composer req "admin:^4"
    symfony console make:admin:dashboard

Далее делаем CRUD интерфейсы для каждой сущности

    symfony console make:admin:crud

Теперь в DashboardController нужно перечислить CRUD админки и выбрать из них дефолтную.
Также для подключенных Entity нужно реализовать метод __toString, чтобы при отображении отношений
они выводились более информативно.

В CRUD контроллерах можно определить методы для кастомизации интерфейса, например

    # локализация, поля для поиска, дефолтная сортировка
    configureCrud(Crud $crud): Crud
    # задать фильтры на список (в т.ч. по связанным сущностям)
    configureFilters(Filters $filters): Filters
    # настройки полей (где показывать, где скрывать, формат полей)
    configureFields(string $pageName): iterable

# Branching the Code

Перед выкладкой можно протестировать проект в отдельном окружении, идентичном боевому

    symfony cloud:deploy
    # откроет сайт вида https://BRANCH_NAME-xxx.eu-5.platformsh.site
    symfony cloud:url -1
    # синхронизировать данные с мастера
    symfony cloud:env:sync
    # включить/выключить dev режим
    symfony cloud:env:debug
    symfony cloud:env:debug --off

Слияние

    git checkout master
    git merge BRANCH_NAME
    symfony cloud:deploy
    git branch -d BRANCH_NAME
    symfony cloud:env:delete -e BRANCH_NAME

# Listening to Events

Диспетчер отправляет определенные события в определенное время, которое могут получать слушатели  
Список событий:

- kernel.request    - Полезно добавить информацию в запрос или заранее вернуть ответ, чтобы остановить обработку запроса
- kernel.controller - после разрешения исполняемого контроллера, но до его выполнения
- kernel.controller_arguments -  
Это событие отправляется непосредственно перед вызовом контроллера.  
Полезно настроить аргументы, которые будут передаваться контроллеру
- kernel.view       - если контроллер не возвращает объект Response
- kernel.response   - если контроллер или любой слушатель kernel.view вернет объект Response
- kernel.finish_request - Полезно сбросить глобальное состояние приложения
- kernel.terminate - Это событие отправляется после отправки ответа
- kernel.exception - Если во время обработки HTTP-запроса возникает ошибка

Посмотреть список слушателей

    php bin/console debug:event-dispatcher EVENT_NAME


# Managing the Lifecycle of Doctrine Objects

Можно использовать события жизенного цикла Doctrine, чтобы автоматизировать
некоторые манипуляциии с Entity.Например, перед первых сохранением:

    #[ORM\PrePersist]
    public function setCreatedAtValue()
    {
           $this->createdAt = new \DateTimeImmutable();
    }

Более гибкий вариант - создать листенер, например для слага (представление сущности в URL)

    // Entity
    public function computeSlug(SluggerInterface $slugger)
    {
           if (!$this->slug || '-' === $this->slug) {
               $this->slug = (string) $slugger->slug((string) $this)->lower();
           }
    }
    ...
    class ConferenceEntityListener
    {
        private $slugger;
    
        public function __construct(SluggerInterface $slugger)
        {
            $this->slugger = $slugger;
        }
    
        public function prePersist(Conference $conference, LifecycleEventArgs $event)
        {
            $conference->computeSlug($this->slugger);
        }
    
        public function preUpdate(Conference $conference, LifecycleEventArgs $event)
        {
            $conference->computeSlug($this->slugger);
        }
    }