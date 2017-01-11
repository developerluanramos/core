# Biigle Export Module

Install the module:

Add the following to the repositories array of your `composer.json`:
```
{
  "type": "vcs",
  "url": "https://github.com/BiodataMiningGroup/biigle-export.git"
}
```

1. Run `php composer.phar require biigle/export`.
2. Add `'Biigle\Modules\Export\ExportServiceProvider'` to the `providers` array in `config/app.php`.
3. Run `php artisan export:publish` to refresh the public assets of this package. Do this for every update of the package.
4. Run `pip install -r vendor/biigle/export/requirements.txt` to install python requirements.
5. Create a `storage/reports` directory that is read/writable for the web application. The location can be configured via the `export.exports_storage` key.
6. Make sure the `export.tmp_storage` directory is read/writable for the web application (default is `sys_get_temp_dir()`).
