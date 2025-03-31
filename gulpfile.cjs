const gulp = require('gulp');
const path = require('path');
const sass = require('gulp-sass')(require('sass'));
const cleanCSS = require('gulp-clean-css');
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');
const sourcemaps = require('gulp-sourcemaps');
const concat = require('gulp-concat');
const order = require('gulp-order');

console.log('-------------------------');
console.log(__dirname);

const dir = __dirname+'/src/Krbe/FileManagerBundle/Resources/public/';
console.log(dir);

// Définition des chemins d'accès aux fichiers source et de destination
const paths = {
    styles: {
        // Chemin vers vos fichiers Sass
        src: path.join(dir, 'css', '*.scss'),
        // Dossier de destination pour les CSS compilés et minifiés
        dest: path.join(dir, 'css')
    },
    scripts: {
        // Chemin vers vos fichiers JavaScript
        src: [path.join(dir, 'js', '*.js'), '!' + path.join(dir, 'js', '*.min.js')],
        // Dossier de destination pour les CSS compilés et minifiés
        dest: path.join(dir, 'js')
    }
};

// Tâches de compilation et minification
function styles() {
    console.log('styles');
    console.log(paths.styles.src);
    return gulp.src(paths.styles.src)
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', sass.logError))
        .pipe(cleanCSS())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.styles.dest));
}

function scripts() {
    return gulp.src(paths.scripts.src)
        .pipe(sourcemaps.init())
        //.pipe(concat('js.js'))
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.scripts.dest));
}

// Tâche build globale (pour usage interne ou test)
const build = gulp.series(gulp.parallel(styles, scripts));
gulp.task('build', build);

// Tâche watch dédiée pour ce projet
gulp.task('watch', function() {
    gulp.watch(paths.styles.src, styles);
    gulp.watch(paths.scripts.src, scripts);
});

gulp.task('default', gulp.parallel('build', 'watch'));