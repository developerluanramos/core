angular.module("dias.projects").controller("ExportController",["$scope","Report","msg","PROJECT",function(e,t,a,n){"use strict";var i=!1,r=["basic annotation","extended annotation","full annotation","annotation CSV","basic image label","image label CSV"],o=[t.getBasic,t.getExtended,t.getFull,t.getCsv,t.getImageLabel,t.getImageLabelCsv],s=[0,1,2,3],c=function(){i=!0},d=function(e){i=!1,a.responseError(e)};e.selected={index:0,option:"0",restrict:!1},e.canBeRestricted=function(){return s.indexOf(e.selected.index)!==-1},e.requestReport=function(){if(void 0!==e.selected.index){var t={};e.canBeRestricted()&&(t.restrict=e.selected.restrict?"1":"0"),o[e.selected.index]({project_id:n.id},t,c,d)}},e.isRequested=function(){return i},e.getSelectedName=function(){return r[e.selected.index]},e.$watch("selected.option",function(t){e.selected.index=parseInt(t)})}]),angular.module("dias.projects").factory("Report",["$resource","URL",function(e,t){"use strict";return e(t+"/api/v1/projects/:project_id/reports/:type/:variant",{},{getBasic:{method:"POST",params:{type:"annotations",variant:"basic"}},getExtended:{method:"POST",params:{type:"annotations",variant:"extended"}},getFull:{method:"POST",params:{type:"annotations",variant:"full"}},getCsv:{method:"POST",params:{type:"annotations",variant:"csv"}},getImageLabel:{method:"POST",params:{type:"image-labels",variant:"basic"}},getImageLabelCsv:{method:"POST",params:{type:"image-labels",variant:"csv"}}})}]);