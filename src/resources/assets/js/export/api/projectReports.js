/**
 * Resource for requesting reports for projects
 *
 * var resource = biigle.$require('export.api.projectReports');
 *
 * Request a basic annotation report:
 *
 * resource.save({id: 1}, {
 *     type_id: 2,
 *     export_area: 1,
 *     separate_label_trees: 0,
 * }).then(...)
 *
 */
biigle.$declare('export.api.projectReports', Vue.resource('/api/v1/projects{/id}/reports'));
