"use strict"
process.env.DISABLE_NOTIFIER = true;

var gulp    = require('gulp');
var elixir  = require('laravel-elixir');
var angular = require('laravel-elixir-angular');

elixir(function (mix) {
	mix.sass('main.scss', 'public/assets/styles/main.css')
	   .angular('resources/assets/js/', 'public/assets/scripts', 'main.js');
    mix.sass('admin.scss', 'public/assets/styles/admin.css')
       .angular('resources/assets/js-admin/', 'public/assets/scripts', 'admin.js');
});

var shell = require('gulp-shell');
gulp.task('docs', shell.task([
	'node_modules/jsdoc/jsdoc.js '+
	'-c node_modules/angular-jsdoc/conf.json '+   // config file
	'-t node_modules/angular-jsdoc/template '+    // template file
	'-d public/doc/client '+                      // output directory
	'-r resources/assets/js'                      // source code directory
]));

var apidoc = require('gulp-apidoc');
gulp.task('apidoc', function (cb) {
	apidoc.exec({
		src: 'app/Http/Controllers/Api/',
		dest: 'public/doc/api'
	}, cb);
});
