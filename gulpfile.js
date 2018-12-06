//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

const gulp    = require('gulp');
const concat  = require('gulp-concat');
const cssnano = require('gulp-cssnano');
const gulpif  = require('gulp-if');
const insert  = require('gulp-insert');
const less    = require('gulp-less');
const plumber = require('gulp-plumber');
const rename  = require('gulp-rename');
const uglify  = require('gulp-uglify');
const yaml    = require('gulp-yaml');
const argv    = require('yargs').argv;

/**
 * Installs vendor fonts to the "public/fonts" folder.
 */
const fontsVendor = () => {

    const files = [
        'node_modules/font-awesome/fonts/*',
    ];

    return gulp.src(files)
        .pipe(gulp.dest('public/fonts/'));
};

/**
 * Installs vendor CSS files as one combined "public/css/vendor.css" asset.
 */
const cssVendor = () => {

    const files = [
        'node_modules/normalize.css/normalize.css',
        'node_modules/font-awesome/css/font-awesome.css',
        'node_modules/dialog-polyfill/dialog-polyfill.css',
    ];

    return gulp.src(files)
        .pipe(gulpif(argv.prod, cssnano({ discardComments: { removeAll: true }})))
        .pipe(concat('vendor.css'))
        .pipe(gulp.dest('public/css/'));
};

/**
 * Installs vendor JavaScript files as one combined "public/js/vendor.js" asset.
 */
const jsVendor = () => {

    const files = [
        argv.prod ? 'node_modules/vue/dist/vue.min.js' : 'node_modules/vue/dist/vue.js',
        'node_modules/axios/dist/axios.js',
        'node_modules/dialog-polyfill/dialog-polyfill.js',
        'assets/etraxis.js',
    ];

    return gulp.src(files)
        .pipe(gulpif(argv.prod, uglify()))
        .pipe(concat('vendor.js'))
        .pipe(gulp.dest('public/js/'));
};

/**
 * Installs stylesheets for LTR languages as one combined "public/css/ltr.css" asset.
 */
const cssLTR = () =>
    gulp.src('node_modules/unsemantic/assets/stylesheets/unsemantic-grid-responsive-no-ie7.css')
        .pipe(gulpif(argv.prod, cssnano({ discardComments: { removeAll: true }})))
        .pipe(concat('ltr.css'))
        .pipe(gulp.dest('public/css/'));

/**
 * Installs stylesheets for RTL languages as one combined "public/css/rtl.css" asset.
 */
const cssRTL = () =>
    gulp.src('node_modules/unsemantic/assets/stylesheets/unsemantic-grid-responsive-no-ie7-rtl.css')
        .pipe(gulpif(argv.prod, cssnano({ discardComments: { removeAll: true }})))
        .pipe(concat('rtl.css'))
        .pipe(gulp.dest('public/css/'));

/**
 * Installs eTraxis CSS theme files as combined "public/css/etraxis-???.css" assets.
 */
const etraxisThemes = () =>
    gulp.src('assets/less/themes/*.less')
        .pipe(plumber())
        .pipe(less())
        .pipe(gulpif(argv.prod, cssnano({ discardComments: { removeAll: true }})))
        .pipe(rename(path => {
            path.basename = `etraxis-${path.basename}`;
            path.extname  = '.css';
        }))
        .pipe(gulp.dest('public/css/'));

/**
 * Converts eTraxis translation files into JavaScript and installs them to the "publis/js/i18n" folder.
 */
const etraxisTranslations = () =>
    gulp.src('translations/messages/messages.*.yaml')
        .pipe(plumber())
        .pipe(yaml({ space: 4 }))
        .pipe(insert.prepend('Object.assign(window.i18n, '))
        .pipe(insert.prepend('window.i18n = window.i18n || {};\n'))
        .pipe(insert.append(');\n'))
        .pipe(rename(path => {
            path.basename = path.basename.replace('messages.', 'etraxis-');
            path.extname  = '.js';
        }))
        .pipe(gulpif(argv.prod, uglify()))
        .pipe(gulp.dest('public/js/i18n/'));

/**
 * Watches for changes in source files and updates affected assets when necessary.
 */
gulp.watch('assets/less/**/*.less',                 gulp.parallel(etraxisThemes));
gulp.watch('translations/messages/messages.*.yaml', gulp.parallel(etraxisTranslations));

/**
 * Performs all installation tasks in one.
 */
gulp.task('default', gulp.series(gulp.parallel(
    fontsVendor,            // install vendor fonts to the "public/fonts" folder
    cssVendor,              // install vendor CSS files as one combined "public/css/vendor.css" asset
    jsVendor,               // install vendor JavaScript files as one combined "public/js/vendor.js" asset
    cssLTR,                 // install stylesheets for LTR languages as one combined "public/css/ltr.css" asset
    cssRTL,                 // install stylesheets for RTL languages as one combined "public/css/rtl.css" asset
    etraxisThemes,          // install eTraxis CSS theme files as combined "public/css/etraxis-???.css" assets
    etraxisTranslations,    // convert eTraxis translation files into JavaScript and install them to the "publis/js/i18n" folder
)));
