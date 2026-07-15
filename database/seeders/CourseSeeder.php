<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\CourseDependency;
use App\Models\Admin\Project;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $this->createCourses();
    }

    private function createCourses(): void
    {
        // --- Course 1: Основы программирования ---
        $course1 = Course::firstOrCreate(
            ['slug' => 'osnovy-programmirovaniya'],
            [
                'title' => 'Основы программирования',
                'description' => 'Стартовый курс для будущих разработчиков. Изучите основы Git, командной строки, алгоритмов и базового программирования. Пройдите 5 pet-проектов с автоматическими тестами.',
                'learning_objectives' => "Понимать основы работы с терминалом\nОсвоить Git: init, clone, commit, push, pull, branch\nПисать базовые скрипты на Python\nРешать алгоритмические задачи\nРаботать с pet-проектами в GitLab",
                'cover_image' => 'courses/basics.png',
                'difficulty' => 'beginner',
                'estimated_hours' => 40,
                'order_position' => 1,
                'is_published' => true,
                'is_featured' => true,
                'created_by' => 1,
            ]
        );

        // Module 1.1: Знакомство с платформой
        $m1_1 = $this->createModule($course1, 1, 'Знакомство с платформой', 'Регистрация, настройка профиля, создание GitLab токена');
        $this->createLesson($m1_1, 1, 'Добро пожаловать в 21 LMS', 'text', null, 15, true, true,
            '<h2>Добро пожаловать!</h2>
<p>21 LMS — это образовательная платформа с последовательным обучением. Вы проходите курсы, выполняете pet-проекты и получаете доступ к следующим направлениям.</p>
<h3>Как работать с платформой:</h3>
<ol>
<li>Выберите курс из каталога</li>
<li>Пройдите модули последовательно</li>
<li>Выполните pet-проекты</li>
<li>Получите доступ к следующим курсам</li>
</ol>');
        $this->createLesson($m1_1, 2, 'Настройка профиля', 'text', null, 20, true, false,
            '<h2>Настройка профиля</h2>
<p>Для работы с GitLab вам нужен профиль:</p>
<ol>
<li>Перейдите в <strong>Профиль → Настройки</strong></li>
<li>Заполните имя и аватар</li>
<li>Сгенерируйте <strong>Personal Access Token</strong> в GitLab: Settings → Access Tokens</li>
<li>Вставьте токен в настройки профиля</li>
</ol>');

        // Module 1.2: Терминал
        $m1_2 = $this->createModule($course1, 2, 'Терминал', 'Базовые команды Linux/Unix');
        $this->createLesson($m1_2, 1, 'Что такое терминал', 'text', null, 15, true, true,
            '<h2>Что такое терминал?</h2>
<p>Терминал (shell) — это программа для управления компьютером через текстовые команды. В разработке Unix-терминалы (bash, zsh) используются повсеместно.</p>
<h3>Основные команды:</h3>
<ul>
<li><code>pwd</code> — показать текущую директорию</li>
<li><code>ls</code> — список файлов</li>
<li><code>cd</code> — сменить директорию</li>
<li><code>mkdir</code> — создать директорию</li>
<li><code>touch</code> — создать файл</li>
</ul>');
        $this->createLesson($m1_2, 2, 'Навигация по файловой системе', 'text', null, 25, true, false,
            '<h2>Навигация</h2>
<p>Работа с путями — основа работы в терминале.</p>
<h3>Абсолютные и относительные пути:</h3>
<ul>
<li>Абсолютный: <code>/home/user/project</code> — от корня</li>
<li>Относительный: <code>./project</code> — от текущей директории</li>
</ul>
<h3>Ключевые команды:</h3>
<pre><code>cd /home/user      # перейти в /home/user
cd ..              # подняться на уровень выше
cd ~               # перейти в домашнюю директорию
ls -la             # показать все файлы с деталями</code></pre>');
        $this->createLesson($m1_2, 3, 'Работа с файлами', 'text', null, 25, true, false,
            '<h2>Работа с файлами</h2>
<p>Основные операции с файлами и директориями.</p>
<pre><code># Создание
touch file.txt
mkdir mydir

# Копирование
cp file.txt file-copy.txt
cp -r mydir/ mydir-backup/

# Перемещение/переименование
mv file.txt newname.txt
mv file.txt ../directory/

# Удаление
rm file.txt
rm -r mydir/

# Просмотр содержимого
cat file.txt
head -n 10 file.txt
tail -f log.txt</code></pre>');

        // Module 1.3: Git
        $m1_3 = $this->createModule($course1, 3, 'Git', 'Система контроля версий: от init до push');
        $this->createLesson($m1_3, 1, 'Git init и clone', 'text', null, 20, true, true,
            '<h2>Git: инициализация и клонирование</h2>
<p>Git — распределённая система контроля версий. Каждый разработчик работает со своей копией репозитория.</p>
<pre><code># Инициализация нового репозитория
git init

# Клонирование существующего
git clone https://gitlab.com/user/project.git

# Проверка статуса
git status

# Просмотр истории
git log --oneline</code></pre>');
        $this->createLesson($m1_3, 2, 'Commit и push', 'text', null, 25, true, false,
            '<h2>Commit и push</h2>
<p>Commit — сохранение изменений. Push — отправка на сервер.</p>
<pre><code># Добавление файлов
git add file.txt           # один файл
git add .                  # все файлы

# Commit
git commit -m "Add feature"

# Push на сервер
git push origin main</code></pre>');
        $this->createLesson($m1_3, 3, 'Branch и merge', 'text', null, 25, true, false,
            '<h2>Branch и merge</h2>
<p>Ветвление — основа работы в команде.</p>
<pre><code># Создание и переключение
git branch feature
git checkout feature
# Или одним comand:
git checkout -b feature

# Merge
git checkout main
git merge feature

# Push ветки
git push -u origin feature</code></pre>');

        // Pet-проекты для курса 1
        $this->createProject($course1, null, 'Hello World в терминале', 'Создайте файл и выведите текст через terminal', 'text', 'beginner', 2, 50, true, true, true, ['bash', 'terminal']);
        $this->createProject($course1, null, 'Текстовый калькулятор', 'Напишите скрипт-калькулятор на bash', 'python', 'beginner', 3, 100, true, true, true, ['python', 'calculator']);
        $this->createProject($course1, null, 'Менеджер задач', 'CLI todo-лист с CRUD операциями', 'python', 'beginner', 4, 150, true, true, true, ['python', 'cli']);
        $this->createProject($course1, null, 'Система погоды', 'API запрос к погодному сервису', 'python', 'intermediate', 5, 200, true, true, true, ['python', 'api']);
        $this->createProject($course1, null, 'Конвертер данных', 'Конвертация между форматами (JSON, CSV, XML)', 'python', 'intermediate', 6, 200, true, true, true, ['python', 'data']);

        // --- Course 2: Backend ---
        $course2 = Course::firstOrCreate(
            ['slug' => 'backend'],
            [
                'title' => 'Backend разработка',
                'description' => 'Изучите основы backend: API, базы данных, серверная логика. Создадите REST API для блога и чат-бота.',
                'learning_objectives' => "Понимать HTTP и REST\nСоздавать REST API\nРаботать с базами данных (PostgreSQL)\nРеализовывать аутентификацию\nПисать серверную логику",
                'cover_image' => 'courses/backend.png',
                'difficulty' => 'intermediate',
                'estimated_hours' => 60,
                'order_position' => 2,
                'is_published' => true,
                'is_featured' => false,
                'created_by' => 1,
            ]
        );

        $m2_1 = $this->createModule($course2, 1, 'Основы Backend', 'Что такое сервер, API, HTTP');
        $this->createLesson($m2_1, 1, 'Что такое сервер', 'text', null, 20, true, true,
            '<h2>Что такое сервер?</h2>
<p>Сервер — программа, которая обрабатывает запросы клиентов (браузеров) и возвращает ответы.</p>
<h3>Как работает HTTP:</h3>
<ol>
<li>Клиент отправляет <strong>запрос</strong> (GET, POST, PUT, DELETE)</li>
<li>Сервер обрабатывает запрос</li>
<li>Сервер возвращает <strong>ответ</strong> (HTML, JSON)</li>
</ol>');
        $this->createLesson($m2_1, 2, 'REST API', 'text', null, 30, true, false,
            '<h2>REST API</h2>
<p>REST — стиль архитектуры для сетевых API.</p>
<ul>
<li><strong>GET /posts</strong> — получить список постов</li>
<li><strong>GET /posts/1</strong> — получить пост с ID=1</li>
<li><strong>POST /posts</strong> — создать новый пост</li>
<li><strong>PUT /posts/1</strong> — обновить пост</li>
<li><strong>DELETE /posts/1</strong> — удалить пост</li>
</ul>');
        $this->createLesson($m2_1, 3, 'Базы данных', 'text', null, 25, true, false,
            '<h2>Базы данных</h2>
<p>Реляционные базы данных (PostgreSQL, MySQL) хранят данные в таблицах.</p>
<pre><code>CREATE TABLE posts (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);</code></pre>');

        $m2_2 = $this->createModule($course2, 2, 'Фреймворки', 'Django / Laravel / Express');
        $this->createLesson($m2_2, 1, 'Выбор фреймворка', 'text', null, 20, true, true,
            '<h2>Выбор фреймворка</h2>
<p>Фреймворк — готовый каркас для разработки. Популярные варианты:</p>
<ul>
<li><strong>Django (Python)</strong> — batteries included, ORM встроен</li>
<li><strong>Laravel (PHP)</strong> — элегантный синтаксис</li>
<li><strong>Express (Node.js)</strong> — минимализм</li>
</ul>');
        $this->createLesson($m2_2, 2, 'Маршрутизация', 'text', null, 25, true, false,
            '<h2>Маршрутизация</h2>
<pre><code>// Express
app.get(\'/posts\', getPosts);
app.post(\'/posts\', createPost);

// Laravel
Route::get(\'/posts\', [PostController::class, \'index\']);
Route::post(\'/posts\', [PostController::class, \'store\']);</code></pre>');

        $this->createProject($course2, null, 'REST API блог', 'API для блога с постами и комментариями', 'python', 'intermediate', 1, 200, true, true, true, ['python', 'api', 'blog']);
        $this->createProject($course2, null, 'Чат-бот', 'Telegram бот с серверной логикой', 'python', 'intermediate', 2, 250, true, true, true, ['python', 'telegram', 'bot']);

        // --- Course 3: Frontend ---
        $course3 = Course::firstOrCreate(
            ['slug' => 'frontend'],
            [
                'title' => 'Frontend разработка',
                'description' => 'HTML, CSS, JavaScript, React. Создайте SPA-приложение и лендинг с современным дизайном.',
                'learning_objectives' => "Освоить HTML5 и CSS3\nПонимать JavaScript ES2022\nРаботать с React\nСоздавать адаптивные интерфейсы\nПонимать REST API интеграцию",
                'cover_image' => 'courses/frontend.png',
                'difficulty' => 'intermediate',
                'estimated_hours' => 55,
                'order_position' => 3,
                'is_published' => true,
                'is_featured' => false,
                'created_by' => 1,
            ]
        );

        $m3_1 = $this->createModule($course3, 1, 'Основы Frontend', 'HTML, CSS, JS');
        $this->createLesson($m3_1, 1, 'HTML5 семантика', 'text', null, 25, true, true,
            '<h2>HTML5 семантика</h2>
<p>Правильная семантика — основа доступности и SEO.</p>
<pre><code>&lt;header&gt;
  &lt;nav aria-label="Main"&gt;...&lt;/nav&gt;
&lt;/header&gt;
&lt;main&gt;
  &lt;section aria-labelledby="hero"&gt;
    &lt;h1 id="hero"&gt;Заголовок&lt;/h1&gt;
  &lt;/section&gt;
&lt;/main&gt;
&lt;footer&gt;...&lt;/footer&gt;</code></pre>');
        $this->createLesson($m3_1, 2, 'CSS и Tailwind', 'text', null, 30, true, false,
            '<h2>CSS и Tailwind CSS</h2>
<p>Tailwind — utility-first фреймворк. Пишите дизайн прямо в HTML.</p>
<pre><code>&lt;div class="flex items-center gap-4 p-6 bg-white rounded-xl shadow-lg"&gt;
  &lt;img src="avatar.jpg" class="w-12 h-12 rounded-full" /&gt;
  &lt;div&gt;
    &lt;p class="font-bold text-gray-900"&gt;Имя&lt;/p&gt;
    &lt;p class="text-sm text-gray-500"&gt;Описание&lt;/p&gt;
  &lt;/div&gt;
&lt;/div&gt;</code></pre>');
        $this->createLesson($m3_1, 3, 'JavaScript основы', 'text', null, 30, true, false,
            '<h2>JavaScript основы</h2>
<pre><code>// Константы и переменные
const name = \'user\';
let count = 0;

// Async/await
async function fetchData() {
  const res = await fetch(\'/api/data\');
  const data = await res.json();
  return data;
}

// Array methods
const names = users.map(u => u.name);
const active = users.filter(u => u.active);</code></pre>');

        $m3_2 = $this->createModule($course3, 2, 'React', 'Компоненты, хуки, state management');
        $this->createLesson($m3_2, 1, 'Компоненты', 'text', null, 25, true, true,
            '<h2>React компоненты</h2>
<pre><code>function Card({ title, body }) {
  return (
    &lt;div className="p-4 bg-white rounded-xl shadow"&gt;
      &lt;h3&gt;{title}&lt;/h3&gt;
      &lt;p&gt;{body}&lt;/p&gt;
    &lt;/div&gt;
  );
}</code></pre>');
        $this->createLesson($m3_2, 2, 'Hooks', 'text', null, 30, true, false,
            '<h2>React Hooks</h2>
<pre><code>import { useState, useEffect } from \'react\';

function Search() {
  const [query, setQuery] = useState(\'\');
  const [results, setResults] = useState([]);

  useEffect(() => {
    if (!query) return;
    fetch(\'/api/search?q=\' + query)
      .then(r => r.json())
      .then(setResults);
  }, [query]);

  return (
    &lt;input value={query} onChange={e => setQuery(e.target.value)} /&gt;
  );
}</code></pre>');

        $this->createProject($course3, null, 'Landing page', 'Современный лендинг с анимациями', 'html', 'beginner', 1, 150, true, true, true, ['html', 'css', 'landing']);
        $this->createProject($course3, null, 'Task manager SPA', 'SPA приложение на React для управления задачами', 'react', 'intermediate', 2, 300, true, true, true, ['react', 'spa', 'typescript']);

        // --- Course 4: Full-Stack ---
        $course4 = Course::firstOrCreate(
            ['slug' => 'fullstack'],
            [
                'title' => 'Full-Stack разработка',
                'description' => 'Интеграция frontend и backend. Создайте полноценный блог с React фронтендом и API бэкендом.',
                'learning_objectives' => "Интегрировать frontend с backend\nРаботать с аутентификацией (JWT)\nРазворачивать приложения\nПонимать CI/CD\nПроектировать архитектуру",
                'cover_image' => 'courses/fullstack.png',
                'difficulty' => 'advanced',
                'estimated_hours' => 80,
                'order_position' => 4,
                'is_published' => true,
                'is_featured' => false,
                'created_by' => 1,
            ]
        );

        $m4_1 = $this->createModule($course4, 1, 'Интеграция', 'Frontend + Backend');
        $this->createLesson($m4_1, 1, 'API интеграция', 'text', null, 25, true, true,
            '<h2>API интеграция</h2>
<p>Frontend общается с backend через REST API.</p>
<pre><code>// Отправка данных на сервер
const res = await fetch(\'/api/posts\', {
  method: \'POST\',
  headers: { \'Content-Type\': \'application/json\' },
  body: JSON.stringify({ title, body })
});
const post = await res.json();</code></pre>');
        $this->createLesson($m4_1, 2, 'Аутентификация', 'text', null, 30, true, false,
            '<h2>Аутентификация</h2>
<p>JWT (JSON Web Tokens) — стандарт для API аутентификации.</p>
<pre><code>// Login
const res = await fetch(\'/api/login\', {
  method: \'POST\',
  body: JSON.stringify({ email, password })
});
const { token } = await res.json();

// Использовать токен
fetch(\'/api/profile\', {
  headers: { \'Authorization\': \'Bearer \' + token }
});</code></pre>');
        $this->createLesson($m4_1, 3, 'Развёртывание', 'text', null, 25, true, false,
            '<h2>Развёртывание</h2>
<p>Основные этапы деплоя:</p>
<ol>
<li>Запуск тестов</li>
<li>Сборка билда</li>
<li>Отправка на сервер</li>
<li>Перезапуск сервиса</li>
</ol>
<p>Платформы: Vercel (frontend), Railway (backend), DigitalOcean, AWS.</p>');

        $this->createProject($course4, null, 'Полноценный блог', 'Блог с React админкой и REST API бэкендом', 'python', 'advanced', 1, 400, true, true, true, ['fullstack', 'blog', 'react']);

        // --- Course Dependencies (DAG) ---
        // Backend depends on Basics
        CourseDependency::firstOrCreate(
            ['course_id' => $course2->id, 'depends_on_course_id' => $course1->id],
            ['order_position' => 1]
        );

        // Frontend depends on Basics
        CourseDependency::firstOrCreate(
            ['course_id' => $course3->id, 'depends_on_course_id' => $course1->id],
            ['order_position' => 1]
        );

        // Full-Stack depends on Backend + Frontend
        CourseDependency::firstOrCreate(
            ['course_id' => $course4->id, 'depends_on_course_id' => $course2->id],
            ['order_position' => 1]
        );
        CourseDependency::firstOrCreate(
            ['course_id' => $course4->id, 'depends_on_course_id' => $course3->id],
            ['order_position' => 2]
        );
    }

    private function createModule(Course $course, int $position, string $title, string $description): Module
    {
        return Module::firstOrCreate(
            ['course_id' => $course->id, 'slug' => $this->slug($title)],
            [
                'title' => $title,
                'description' => $description,
                'order_position' => $position,
                'is_published' => true,
            ]
        );
    }

    private function createLesson(Module $module, int $position, string $title, string $contentType, ?string $videoUrl, int $minutes, bool $published, bool $isFree, string $content): Lesson
    {
        return Lesson::firstOrCreate(
            ['module_id' => $module->id, 'slug' => $this->slug($title)],
            [
                'title' => $title,
                'content' => $content,
                'content_type' => $contentType,
                'video_url' => $videoUrl,
                'estimated_minutes' => $minutes,
                'order_position' => $position,
                'is_published' => $published,
                'is_free' => $isFree,
            ]
        );
    }

    private function createProject(Course $course, ?Module $module, string $title, string $description, string $language, string $difficulty, int $orderPosition, int $xpReward, bool $published, bool $hasTests, bool $requiresReview, array $tags = []): Project
    {
        return Project::firstOrCreate(
            ['course_id' => $course->id, 'slug' => $this->slug($title)],
            [
                'title' => $title,
                'description' => $description,
                'instructions' => "Выполните задание: создайте проект по описанию и отправьте код через GitLab.",
                'module_id' => $module?->id,
                'difficulty' => $difficulty,
                'estimated_hours' => $orderPosition,
                'xp_reward' => $xpReward,
                'is_published' => $published,
                'has_automated_tests' => $hasTests,
                'requires_peer_review' => $requiresReview,
                'language' => $language,
                'tags' => $tags,
                'submission_type' => 'git',
                'passing_score' => 80,
                'required_reviews_count' => 2,
                'created_by' => 1,
            ]
        );
    }

    private function slug(string $text): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9а-яА-ЯёЁ _-]/u', '', str_replace(' ', '-', $text)));
    }
}
