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
const uglify  = require('gulp-uglify');
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
 * Performs all installation tasks in one.
 */
gulp.task('default', gulp.series(gulp.parallel(
    fontsVendor,            // install vendor fonts to the "public/fonts" folder
    cssVendor,              // install vendor CSS files as one combined "public/css/vendor.css" asset
    jsVendor,               // install vendor JavaScript files as one combined "public/js/vendor.js" asset
    cssLTR,                 // install stylesheets for LTR languages as one combined "public/css/ltr.css" asset
    cssRTL,                 // install stylesheets for RTL languages as one combined "public/css/rtl.css" asset
)));
