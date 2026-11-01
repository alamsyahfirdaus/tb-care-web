    <?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AssessmentController;
    use App\Http\Controllers\AssessorController;
    use App\Http\Controllers\AssmntResultController;
    use App\Http\Controllers\ElementController;
    use App\Http\Controllers\CompStdController;
    use App\Http\Controllers\HomeController;
    use App\Http\Controllers\LoginController;
    use App\Http\Controllers\MajorController;
    use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

    /*
    |--------------------------------------------------------------------------
    | Web Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register web routes for your application. These
    | routes are loaded by the RouteServiceProvider and all of them will
    | be assigned to the "web" middleware group. Make something great!
    |
    */

    // Route::get('/', function () {
    //     return view('welcome');
    // });

    Route::get('/', [LoginController::class, 'index'])->name('login')->middleware('guest');

    Route::controller(LoginController::class)->group(function () {
        Route::get('login', 'index')->name('login')->middleware('guest');
        Route::post('login', 'authenticate');
        Route::get('logout', 'logout')->name('logout');
    });

    Route::middleware('auth')->group(function () {

        Route::get('home', [HomeController::class, 'index'])->name('home');
        Route::get('profile', [HomeController::class, 'profile'])->name('profile');
        Route::match(['post', 'put'], 'user/store/{id?}', [HomeController::class, 'store'])->name('user.store');


        Route::middleware('checkRole:administrator')->group(function () {
            Route::get('add', [StudentController::class, 'addStudent'])->name('student.add');
            // Route::get('years', [HomeController::class, 'years'])->name('years');
            // Route::get('classes', [HomeController::class, 'classes'])->name('classes');

            Route::get('majors', [MajorController::class, 'index'])->name('majors');
            Route::get('major/{id}/edit', [MajorController::class, 'edit'])->name('major.edit');
            Route::match(['post', 'put'], 'major/store/{id?}', [MajorController::class, 'store'])->name('major.store');
            Route::delete('major/{id}', [MajorController::class, 'destroy'])->name('major.delete');

            Route::get('subjects', [SubjectController::class, 'index'])->name('subjects');
            Route::get('subject/{id}/edit', [SubjectController::class, 'edit'])->name('subject.edit');
            Route::match(['post', 'put'], 'subject/store/{id?}', [SubjectController::class, 'store'])->name('subject.store');
            Route::delete('subject/{id}', [SubjectController::class, 'destroy'])->name('subject.delete');

            Route::get('teachers', [AssessorController::class, 'index'])->name('teachers');
            Route::get('teacher/{id}/edit', [AssessorController::class, 'show'])->name('teacher.show');
            
        });

        Route::middleware('checkRole:administrator-assessor_department_head')->group(function () {
            Route::get('students', [StudentController::class, 'index'])->name('students');

            Route::get('assessors', [AssessorController::class, 'index'])->name('assessors');
            Route::get('assessor/{id}/edit', [AssessorController::class, 'show'])->name('assessor.show');
            Route::match(['post', 'put'], 'assessor/store/{id?}', [AssessorController::class, 'store'])->name('assessor.store');
            Route::delete('assessor/{id}', [AssessorController::class, 'destroy'])->name('assessor.delete');
        });

        Route::middleware('checkRole:administrator-assessor_internal')->group(function () {
            Route::get('achievements', [AchievementController::class, 'index'])->name('achievements');
            Route::get('achievement/{id}/list', [AchievementController::class, 'list'])->name('achievement.list');
            Route::get('achievement/{id}/edit', [AchievementController::class, 'edit'])->name('achievement.edit');
            Route::match(['post', 'put'], 'achievement/store/{id?}', [AchievementController::class, 'store'])->name('achievement.store');
            Route::delete('achievement/{id}', [AchievementController::class, 'destroy'])->name('achievement.delete');

            Route::get('element/{id}/list', [ElementController::class, 'list'])->name('element.list');
            Route::get('element/{id}/edit', [ElementController::class, 'edit'])->name('element.edit');
            Route::match(['post', 'put'], 'element/store/{id?}', [ElementController::class, 'store'])->name('element.store');
            Route::delete('element/{id}', [ElementController::class, 'destroy'])->name('element.delete');

            Route::get('criteria/{id}/list', [ElementController::class, 'listCriteria'])->name('criteria.list');
            Route::get('criteria/{id}/edit', [ElementController::class, 'editCriteria'])->name('criteria.edit');
            Route::match(['post', 'put'], 'criteria/store/{id?}', [ElementController::class, 'saveCriteria'])->name('criteria.store');
            Route::delete('criteria/{id}', [ElementController::class, 'deleteCriteria'])->name('criteria.delete');

            Route::get('asmnt/{id}/edit', [AssessmentController::class, 'edit'])->name('assessment.edit');
            Route::match(['post', 'put'], 'asmnt/store/{id?}', [AssessmentController::class, 'store'])->name('assessment.store');
            Route::delete('asmnt/{id}', [AssessmentController::class, 'destroy'])->name('assessment.delete');
            Route::get('compstdmajor/{id?}', [AssessmentController::class, 'getCompstdMajor'])->name('compstd.major');
        });

        Route::middleware('checkRole:administrator-assessor')->group(function () {
            Route::get('asmnt/{id}/show', [AssessmentController::class, 'show'])->name('assessment.show');
            Route::get('asmnt/{assessment_id}/{student_id}/list', [AssessmentController::class, 'list'])->name('assessment.list');

            Route::get('assmntresults', [AssmntResultController::class, 'index'])->name('assmntresults');
            Route::match(['post', 'put'], 'assmntresult/store/{id?}', [AssmntResultController::class, 'store'])->name('assmntresult.store');
            Route::get('assmntresult/{id}/show', [AssmntResultController::class, 'show'])->name('asmnt.result');
        });

        Route::middleware('checkRole:administrator-assessor-student')->group(function () {
            Route::get('assessments', [AssessmentController::class, 'index'])->name('assessments');
        });
    });
