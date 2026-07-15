<?php

namespace Database\Seeders;

use App\Models\Admin\Project;
use App\Services\GitlabService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Seeds REAL School 21 core-program projects (C / C++ track).
 *
 * Sources (public repos of School 21 students / topic "s21" on GitHub):
 *   - C2_SimpleBashUtils  (cat/grep)
 *   - C4_s21_math         (math.h)
 *   - C5_s21_decimal      (decimal arithmetic)
 *   - C5_s21_string+      (string.h + sprintf)
 *   - C6_s21_matrix       (matrix.h)
 *   - C7_SmartCalc_v1.0   (calculator, RPN/Dijkstra)
 *   - C7_BrickGame_v1.0   (Tetris, ncurses)
 *   - C8_3DViewer_v1.0    (wireframe 3D viewer)
 *   - CPP1_s21_matrix+    (matrix OOP, C++)
 *   - CPP2_s21_containers (STL containers, C++)
 *
 * Each project is created in the local GitLab first (createProjectWithConfig),
 * then mirrored to the DB. Existing slugs are skipped, so the seeder is
 * idempotent and safe to re-run.
 */
class ProjectSeeder extends Seeder
{
    public function run(GitlabService $gitlab): void
    {
        $projects = [
            [
                'title' => 'SimpleBashUtils',
                'slug' => 'simple-bash-utils',
                'description' => 'Разработка собственных версий утилит cat и grep на языке C: флаги, потоки, регулярные выражения.',
                'instructions' => "# C2_SimpleBashUtils\n\n## Описание\nРеализация утилит **s21_cat** и **s21_grep** — аналогов GNU cat/grep.\n\n## Часть 1. s21_cat\n- Поддержка флагов: `-b`, `-e`, `-n`, `-s`, `-t`, `-v` (+ GNU-версии `--number-nonblank`, `--number`, `--squeeze-blank`).\n- Программа в `src/cat/`, сборка Makefile (цель `s21_cat`).\n\n## Часть 2. s21_grep\n- Поддержка флагов: `-e`, `-i`, `-v`, `-c`, `-l`, `-n`, `-h`, `-s`, `-f`, `-o` и их комбинаций.\n- Библиотека `regex.h` (POSIX), запрещены сторонние библиотеки.\n- Программа в `src/grep/`, сборка Makefile (цель `s21_grep`).\n\n## Общие требования\n- Язык C11, компилятор gcc, POSIX.1-2017.\n- Стиль Google Style, без утечек памяти (проверка leaks/valgrind).\n- Интеграционные тесты, сравнение вывода с оригинальными утилитами.\n\n## Критерии приёмки\n- Вывод s21_cat/s21_grep побайтово совпадает с cat/grep на тестовых наборах.\n- Все флаги и их комбинации работают.\n- Makefile собирает обе утилиты без предупреждений `-Wall -Werror -Wextra`.",
                'difficulty' => 'beginner',
                'min_level' => 1,
                'estimated_hours' => 12,
                'language' => 'c',
                'xp_reward' => 100,
                'passing_score' => 70,
            ],
            [
                'title' => 's21_string+',
                'slug' => 's21-stringplus',
                'description' => 'Реализация библиотеки string.h на C с собственной версией sprintf и дополнительными функциями.',
                'instructions' => "# C5_s21_string+\n\n## Описание\nСобственная реализация библиотеки **string.h** (s21_string.h) + частичная реализация **sprintf**.\n\n## Часть 1. string.h\nФункции: memchr, memcmp, memcpy, memset, strncat, strchr, strncmp, strncpy, strcspn, strerror, strlen, strpbrk, strrchr, strstr, strtok.\n\n## Часть 2. s21_sprintf\n- Спецификаторы: c, d, f, s, u, %.\n- Флаги: -, +, (пробел); ширина, точность, длина (h, l).\n\n## Часть 3. Дополнительно (бонус)\n- Спецификаторы g, G, e, E, x, X, o, p; флаги #, 0.\n- Функции s21_to_upper, s21_to_lower, s21_insert, s21_trim.\n\n## Требования\n- Язык C11, static-библиотека s21_string.a, Makefile (цели all, clean, test, s21_string.a, gcov_report).\n- Покрытие unit-тестами (Check) каждой функции: обычные значения, граничные, аномальные.\n- Покрытие ≥ 80% каждой функции по gcov.\n\n## Критерии приёмки\n- Поведение функций совпадает с оригинальной библиотекой.\n- gcov_report формирует html-отчёт покрытия.",
                'difficulty' => 'beginner',
                'min_level' => 1,
                'estimated_hours' => 30,
                'language' => 'c',
                'xp_reward' => 250,
                'passing_score' => 75,
            ],
            [
                'title' => 's21_math',
                'slug' => 's21-math',
                'description' => 'Собственная реализация библиотеки math.h: тригонометрия, экспонента, логарифм с точностью до 16 знаков.',
                'instructions' => "# C4_s21_math\n\n## Описание\nРеализация основных функций **math.h**: s21_abs, s21_acos, s21_asin, s21_atan, s21_ceil, s21_cos, s21_exp, s21_fabs, s21_floor, s21_fmod, s21_log, s21_pow, s21_sin, s21_sqrt, s21_tan.\n\n## Требования\n- Язык C11, static-библиотека s21_math.a, Makefile (all, clean, test, s21_math.a, gcov_report).\n- Точность 16 значащих цифр, дробная часть — 6 знаков.\n- Обработка краевых случаев: NAN, INFINITY, отрицательные аргументы.\n- Unit-тесты (Check) с проверкой против оригинальной math.h, покрытие ≥ 80%.\n\n## Критерии приёмки\n- Расхождение с math.h не превышает 1e-6 на тестовых наборах.\n- Никаких запрещённых вызовов оригинальной библиотеки внутри реализации.",
                'difficulty' => 'intermediate',
                'min_level' => 1,
                'estimated_hours' => 20,
                'language' => 'c',
                'xp_reward' => 300,
                'passing_score' => 75,
            ],
            [
                'title' => 's21_decimal',
                'slug' => 's21-decimal',
                'description' => 'Библиотека для работы с типом decimal: банковская арифметика без потери точности на C.',
                'instructions' => "# C5_s21_decimal\n\n## Описание\nРеализация типа **decimal** (128-битное представление: 96-бит мантисса + степень 10) и операций над ним. Тип критичен для финансовых расчётов, где недопустимы ошибки округления float/double.\n\n## Арифметика\n- s21_add, s21_sub, s21_mul, s21_div, s21_mod (коды ошибок: OK, +INF, -INF, div by zero).\n\n## Сравнение\n- s21_is_less, s21_is_less_or_equal, s21_is_greater, s21_is_greater_or_equal, s21_is_equal, s21_is_not_equal.\n\n## Преобразователи\n- s21_from_int_to_decimal, s21_from_float_to_decimal, s21_from_decimal_to_int, s21_from_decimal_to_float.\n\n## Округление\n- s21_floor, s21_round, s21_truncate, s21_negate.\n\n## Требования\n- Язык C11, битовые операции (запрещён тип __int128), static-библиотека s21_decimal.a.\n- Банковское округление, unit-тесты Check, покрытие ≥ 80%.\n\n## Критерии приёмки\n- Все операции соответствуют поведению C# System.Decimal на тестовых наборах.",
                'difficulty' => 'intermediate',
                'min_level' => 2,
                'estimated_hours' => 35,
                'language' => 'c',
                'xp_reward' => 350,
                'passing_score' => 75,
            ],
            [
                'title' => 's21_matrix',
                'slug' => 's21-matrix',
                'description' => 'Библиотека операций над матрицами на C: определитель, обратная матрица, дополнения.',
                'instructions' => "# C6_s21_matrix\n\n## Описание\nРеализация библиотеки **matrix.h** со структурой matrix_t (matrix, rows, columns).\n\n## Функции\n- s21_create_matrix, s21_remove_matrix.\n- s21_eq_matrix (точность 1e-7).\n- s21_sum_matrix, s21_sub_matrix, s21_mult_number, s21_mult_matrix.\n- s21_transpose, s21_calc_complements, s21_determinant, s21_inverse_matrix.\n\n## Коды ошибок\n- 0 — OK; 1 — некорректная матрица; 2 — ошибка вычисления (несовпадение размеров, det = 0 и т.д.).\n\n## Требования\n- Язык C11, static-библиотека s21_matrix.a, Makefile (all, clean, test, s21_matrix.a, gcov_report).\n- Unit-тесты Check, покрытие ≥ 80%, без утечек памяти.\n\n## Критерии приёмки\n- Определитель и обратная матрица считаются корректно на матрицах до 10x10.",
                'difficulty' => 'intermediate',
                'min_level' => 2,
                'estimated_hours' => 15,
                'language' => 'c',
                'xp_reward' => 200,
                'passing_score' => 75,
            ],
            [
                'title' => 'SmartCalc v1.0',
                'slug' => 'smartcalc-v1',
                'description' => 'Инженерный калькулятор с GUI: обратная польская нотация, графики функций, кредитный калькулятор.',
                'instructions' => "# C7_SmartCalc_v1.0\n\n## Описание\nРасширенный калькулятор на C с графическим интерфейсом (GTK/Qt): вычисление выражений с приоритетами, построение графиков.\n\n## Требования\n- Перевод выражения в **обратную польскую нотацию** (алгоритм Дейкстры).\n- Скобки, операторы: + - * / mod ^, унарный ±.\n- Функции: cos, sin, tan, acos, asin, atan, sqrt, ln, log.\n- Переменная x, построение графика функции (масштабирование, оси).\n- Длина выражения до 255 символов, точность 7 знаков.\n- Бонус: кредитный калькулятор (аннуитетный/дифференцированный платёж).\n\n## Архитектура\n- Обязательный паттерн **MVC**: модель без UI-кода, unit-тесты на модель (покрытие ≥ 80%).\n\n## Критерии приёмки\n- Выражения любой валидной вложенности считаются корректно.\n- График строится для произвольной функции из списка.",
                'difficulty' => 'advanced',
                'min_level' => 3,
                'estimated_hours' => 40,
                'language' => 'c',
                'xp_reward' => 500,
                'passing_score' => 80,
            ],
            [
                'title' => 'BrickGame v1.0 (Tetris)',
                'slug' => 'brickgame-v1-tetris',
                'description' => 'Классический тетрис на C: конечный автомат, библиотека игровой логики и терминальный интерфейс ncurses.',
                'instructions' => "# C7_BrickGame_v1.0\n\n## Описание\nРеализация игры **Тетрис** в консоли: отдельная библиотека игровой логики + CLI-интерфейс на ncurses.\n\n## Требования\n- Язык C11, структурное программирование, логика в `src/brick_game/tetris`, GUI в `src/gui/cli`.\n- Конечный автомат состояний игры (документировать диаграмму).\n- Механики: вращение фигуры, перемещение по горизонтали, ускоренное падение, показ следующей фигуры, очистка заполненных линий, подсчёт очков и рекорд (сохранение в файл), уровни скорости.\n- Makefile: all, install, uninstall, clean, dvi, dist, test, gcov_report.\n- Unit-тесты логики (покрытие ≥ 80%).\n\n## Критерии приёмки\n- Игра идёт по классическим правилам, конец при достижении верхней границы.\n- Рекорд сохраняется между запусками.",
                'difficulty' => 'advanced',
                'min_level' => 3,
                'estimated_hours' => 45,
                'language' => 'c',
                'xp_reward' => 600,
                'passing_score' => 80,
            ],
            [
                'title' => '3DViewer v1.0',
                'slug' => '3dviewer-v1',
                'description' => 'Программа просмотра каркасных 3D-моделей: парсинг .obj, аффинные преобразования, рендер.',
                'instructions' => "# C8_3DViewer_v1.0\n\n## Описание\nПрограмма для просмотра **каркасных 3D-моделей** (wireframe) из файлов .obj с возможностью трансформаций.\n\n## Требования\n- Язык C11, GUI (GTK/Qt/OpenGL), MVC, логика отделена от интерфейса.\n- Парсинг .obj: вершины (v) и поверхности (f); модели до 1 000 000 вершин без зависаний (< 0.5 c на операцию).\n- Аффинные преобразования: перенос, поворот, масштабирование по осям X/Y/Z.\n- Настройки: тип проекции (параллельная/центральная), тип/цвет/толщина рёбер, способ отображения/цвет/размер вершин, цвет фона — с сохранением между запусками.\n- Бонус: скриншоты (bmp/jpeg), запись gif-анимации (640x480, 10fps, 5s).\n- Unit-тесты модулей загрузки и преобразований (покрытие ≥ 80%).\n\n## Критерии приёмки\n- Куб, сложные модели из публичных наборов .obj загружаются и трансформируются корректно.",
                'difficulty' => 'advanced',
                'min_level' => 4,
                'estimated_hours' => 50,
                'language' => 'c',
                'xp_reward' => 750,
                'passing_score' => 80,
            ],
            [
                'title' => 's21_matrix+ (C++)',
                'slug' => 's21-matrixplus',
                'description' => 'Матричная библиотека в объектно-ориентированном стиле: класс S21Matrix, перегрузка операторов.',
                'instructions' => "# CPP1_s21_matrix+\n\n## Описание\nРеализация матричной библиотеки из s21_matrix в **объектно-ориентированном** стиле на C++17: класс `S21Matrix`.\n\n## Требования\n- Приватные поля matrix_, rows_, cols_; accessors/mutators с пересчётом размера.\n- Конструкторы: базовый, параметризованный, копирования, переноса; деструктор.\n- Методы: EqMatrix, SumMatrix, SubMatrix, MulNumber, MulMatrix, Transpose, CalcComplements, Determinant, InverseMatrix.\n- Перегрузка операторов: +, -, *, ==, =, +=, -=, *=, (int i, int j).\n- Исключения при некорректных размерах/выходе за границы.\n- Static-библиотека s21_matrix_oop.a, Makefile (all, clean, test, s21_matrix_oop.a), тесты GTest, покрытие ≥ 80%.\n\n## Критерии приёмки\n- Все операции и операторы соответствуют математическому определению.",
                'difficulty' => 'intermediate',
                'min_level' => 4,
                'estimated_hours' => 15,
                'language' => 'cpp',
                'xp_reward' => 300,
                'passing_score' => 75,
            ],
            [
                'title' => 's21_containers (C++)',
                'slug' => 's21-containers',
                'description' => 'Собственная реализация контейнеров STL: list, map, set, queue, stack, vector с итераторами.',
                'instructions' => "# CPP2_s21_containers\n\n## Описание\nРеализация библиотеки **s21_containers.h** — собственные аналоги стандартных контейнеров STL.\n\n## Часть 1. Основные контейнеры\n- `list`, `map`, `queue`, `set`, `stack`, `vector` — шаблонные классы с полным набором методов (конструкторы, итераторы, capacity, modifiers) по аналогии с STL.\n\n## Часть 2. Бонус\n- `array`, `multiset`.\n- Методы `insert_many` (вариативные шаблоны parameter pack).\n\n## Требования\n- C++17, только заголовочные файлы (header-only), без реализации через STL-контейнеры.\n- Красно-чёрное дерево для map/set (собственная реализация).\n- Тесты GTest на каждый метод каждого контейнера, покрытие ≥ 80%.\n- Makefile (clean, test).\n\n## Критерии приёмки\n- Поведение контейнеров и итераторов идентично STL на тестовых сценариях.\n- Отсутствие утечек памяти (valgrind).",
                'difficulty' => 'advanced',
                'min_level' => 5,
                'estimated_hours' => 60,
                'language' => 'cpp',
                'xp_reward' => 800,
                'passing_score' => 80,
            ],
        ];

        foreach ($projects as $index => $data) {
            $slug = Str::slug($data['slug'] ?? $data['title']);

            // Skip if already exists — seeder is idempotent
            if (Project::where('slug', $slug)->exists()) {
                Log::info("Project '{$data['title']}' already exists, skipping.");
                continue;
            }

            // Try to create in GitLab; if it already exists, find it
            $gitlabProject = null;
            try {
                $gitlabName = $this->transliterate($data['title']);
                $gitlabProject = $gitlab->createProjectWithConfig(
                    name: $gitlabName,
                    path: $slug,
                    description: $data['description'],
                    defaultBranch: 'main',
                );
            } catch (\RuntimeException $e) {
                Log::warning("Failed to create GitLab project '{$data['title']}': " . $e->getMessage());
                // Try to find existing project in GitLab by path
                $allProjects = $gitlab->getProjects();
                $gitlabProject = collect($allProjects)->firstWhere('path', $slug);
            }

            if (!$gitlabProject) {
                Log::error("Could not find or create GitLab project '{$data['title']}' with path '{$slug}'.");
                continue;
            }

            // Create in DB (link to existing or newly created GitLab project)
            Project::create([
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'],
                'instructions' => $data['instructions'],
                'hints' => null,
                'course_id' => null,
                'module_id' => null,
                'difficulty' => $data['difficulty'],
                'min_level' => $data['min_level'],
                'estimated_hours' => $data['estimated_hours'],
                'order_position' => $index + 1,
                'language' => $data['language'],
                'language_version' => null,
                'submission_type' => 'git',
                'allowed_file_extensions' => json_encode($this->getFileExtensions($data['language'])),
                'max_file_size_mb' => 10,
                'has_automated_tests' => true,
                'test_file_path' => null,
                'test_timeout_seconds' => 30,
                'docker_config' => null,
                'xp_reward' => $data['xp_reward'],
                'passing_score' => $data['passing_score'],
                'requires_peer_review' => true,
                'required_reviews_count' => 2,
                'is_published' => true,
                'is_mandatory' => $index < 5, // C-core (первые 5) — обязательные
                'tags' => json_encode([$data['language'], $data['difficulty'], 'school21']),
                'learning_outcomes' => null,
                'created_by' => null,
                'gitlab_project_id' => $gitlabProject['id'],
                'repository_url' => $gitlabProject['web_url'],
                'default_branch' => $gitlabProject['default_branch'] ?? 'main',
                'runtime' => ['command' => $this->getDefaultCommand($data['language']), 'timeout' => 30],
            ]);

            Log::info("Seeded project '{$data['title']}' in DB (GitLab ID: {$gitlabProject['id']}).");
        }
    }

    /**
     * Map languages to default file extensions.
     */
    private function getFileExtensions(string $language): array
    {
        return match ($language) {
            'python' => ['.py'],
            'javascript' => ['.js'],
            'php' => ['.php'],
            'cpp' => ['.cpp', '.h', '.tpp'],
            'c' => ['.c', '.h'],
            'go' => ['.go'],
            'rust' => ['.rs'],
            'java' => ['.java'],
            default => ['.txt'],
        };
    }

    /**
     * Get default runtime command for a language.
     */
    private function getDefaultCommand(string $language): string
    {
        return match ($language) {
            'python' => 'python main.py',
            'javascript' => 'node main.js',
            'php' => 'php main.php',
            'c' => 'gcc -Wall -Werror -Wextra *.c && ./a.out',
            'cpp' => 'g++ -Wall -Werror -Wextra *.cpp && ./a.out',
            'go' => 'go run main.go',
            'rust' => 'cargo run',
            'java' => 'javac Main.java && java Main',
            default => 'echo "No default command"',
        };
    }

    /**
     * Transliterate Cyrillic to Latin for GitLab compatibility.
     */
    private function transliterate(string $text): string
    {
        $map = [
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Shch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];

        return Str::slug(strtr($text, $map), '-');
    }
}
