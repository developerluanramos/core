biigle.$viewModel("annotations-navbar",function(e){new Vue({el:e,data:{currentImageFilename:"",filenameMap:{}},methods:{updateFilename:function(e){this.currentImageFilename=this.filenameMap[e]}},watch:{currentImageFilename:function(e){document.title="Annotate "+e}},created:function(){var e=biigle.$require("biigle.events"),t=biigle.$require("annotations.imagesIds"),n=biigle.$require("annotations.imagesFilenames"),i=this.filenameMap;t.forEach(function(e,t){i[e]=n[t]}),e.$on("images.change",this.updateFilename)}})}),biigle.$viewModel("annotator-container",function(e){var t=biigle.$require("biigle.events"),n=biigle.$require("annotations.imagesIds"),i=biigle.$require("annotations.stores.images"),o=biigle.$require("annotations.stores.annotations"),a=biigle.$require("volumes.urlParams");new Vue({el:e,mixins:[biigle.$require("core.mixins.loader")],components:{sidebar:biigle.$require("annotations.components.sidebar"),sidebarTab:biigle.$require("core.components.sidebarTab"),labelsTab:biigle.$require("annotations.components.labelsTab"),annotationsTab:biigle.$require("annotations.components.annotationsTab"),annotationCanvas:biigle.$require("annotations.components.annotationCanvas")},data:{imageIndex:null,image:null,annotations:[],annotationFilter:null,mapCenter:void 0,mapResolution:void 0,selectedLabel:null},computed:{imageId:function(){return n[this.imageIndex]},selectedAnnotations:function(){return this.annotations.filter(function(e){return e.selected})},hasAnnotationFilter:function(){return"function"==typeof this.annotationFilter},filteredAnnotations:function(){return this.hasAnnotationFilter?this.annotations.filter(this.annotationFilter):this.annotations}},methods:{getImageAndAnnotationsPromises:function(){return[i.fetchImage(this.imageId),o.fetchAnnotations(this.imageId)]},setCurrentImageAndAnnotations:function(e){this.image=e[0],this.annotations=e[1]},updateUrlSlug:function(){a.setSlug(this.imageId)},getNextIndex:function(e){return(e+1)%n.length},getPreviousIndex:function(e){return(e+n.length-1)%n.length},nextImage:function(){this.loading||(this.imageIndex=this.getNextIndex(this.imageIndex))},previousImage:function(){this.loading||(this.imageIndex=this.getPreviousIndex(this.imageIndex))},handleMapMoveend:function(e){this.mapCenter=e.center,this.mapResolution=e.resolution,a.set({r:Math.round(100*e.resolution),x:Math.round(e.center[0]),y:Math.round(e.center[1])})},handleSelectAnnotation:function(e,t){return t&&t.shiftKey?void(e.selected=!0):void this.annotations.forEach(function(t){t.selected=e.id===t.id})},handleSelectAnnotations:function(e,t){e.forEach(function(e){e.selected=!0}),t.forEach(function(e){e.selected=!1}),this.$refs.annotationsTab.scrollIntoView(this.selectedAnnotations)},handleDeselectAnnotation:function(e,t){return t&&t.shiftKey?void(e.selected=!1):void this.annotations.forEach(function(e){e.selected=!1})},handleFocusAnnotation:function(e){this.$refs.canvas.focusAnnotation(e)},maybeSelectAndFocusAnnotation:function(){var e=a.get("annotation");if(e){e=parseInt(e);for(var t=this.annotations,n=t.length-1;n>=0;n--)if(t[n].id===e)return this.handleFocusAnnotation(t[n]),void(t[n].selected=!0)}},handleFilter:function(e){this.annotationFilter=e},handleSelectedLabel:function(e){this.selectedLabel=e}},watch:{imageIndex:function(e){var i=n[this.getPreviousIndex(e)],o=n[this.getNextIndex(e)];t.$emit("images.change",this.imageId,i,o),this.startLoading(),Vue.Promise.all(this.getImageAndAnnotationsPromises()).then(this.setCurrentImageAndAnnotations).then(this.updateUrlSlug).then(this.maybeSelectAndFocusAnnotation).then(this.finishLoading)}},created:function(){this.startLoading();var e=biigle.$require("labelTrees.stores.keyboard");e.on(37,this.previousImage),e.on(32,this.nextImage),e.on(39,this.nextImage),this.imageIndex=n.indexOf(biigle.$require("annotations.imageId")),void 0!==a.get("r")&&(this.mapResolution=parseInt(a.get("r"),10)/100),void 0!==a.get("x")&&void 0!==a.get("y")&&(this.mapCenter=[parseInt(a.get("x"),10),parseInt(a.get("y"),10)]),t.$on("annotations.select",this.handleSelectAnnotation),t.$on("annotations.deselect",this.handleDeselectAnnotation),t.$on("annotations.focus",this.handleFocusAnnotation)}})}),biigle.$component("annotations.components.annotationCanvas",function(){var e,t,n=new ol.layer.Image,i=new ol.source.Vector,o=new ol.layer.Vector({source:i,zIndex:100,updateWhileAnimating:!0,updateWhileInteracting:!0});return{components:{loaderBlock:biigle.$require("core.components.loaderBlock"),minimap:biigle.$require("annotations.components.minimap"),labelIndicator:biigle.$require("annotations.components.labelIndicator")},props:{image:{type:HTMLCanvasElement},annotations:{type:Array,default:function(){return[]}},selectedAnnotations:{type:Array,default:function(){return[]}},loading:{type:Boolean,default:!1},center:{type:Array,default:void 0},resolution:{type:Number,default:void 0},selectedLabel:{default:null}},data:function(){biigle.$require("annotations.stores.styles");return{initialized:!1,viewFitOptions:{padding:[50,50,50,50],minResolution:1}}},computed:{extent:function(){return this.image?[0,0,this.image.width,this.image.height]:[0,0,0,0]},projection:function(){return new ol.proj.Projection({code:"biigle-image",units:"pixels",extent:this.extent})},selectFeatures:function(){return t?t.getFeatures():[]}},methods:{getGeometry:function(e){for(var t=e.points,n=[],i=this.image.height,o=0;o<t.length;o+=2)n.push([t[o],i-(t[o+1]||0)]);switch(e.shape){case"Point":return new ol.geom.Point(n[0]);case"Rectangle":return new ol.geom.Rectangle([n]);case"Polygon":return new ol.geom.Polygon([n]);case"LineString":return new ol.geom.LineString(n);case"Circle":return new ol.geom.Circle(n[0],n[1][0]);default:return void console.error("Unknown annotation shape: "+e.shape)}},createFeature:function(e){var t=new ol.Feature({geometry:this.getGeometry(e)});return t.setId(e.id),t.set("annotation",e),e.labels&&e.labels.length>0&&t.set("color",e.labels[0].label.color),t},focusAnnotation:function(t){var n=i.getFeatureById(t.id);if(n){var o=e.getView(),a=ol.animation.pan({source:o.getCenter()}),r=ol.animation.zoom({resolution:o.getResolution()});e.beforeRender(a,r),o.fit(n.getGeometry(),e.getSize(),this.viewFitOptions)}},handleFeatureSelect:function(e){var t=function(e){return e.get("annotation")};this.$emit("select",e.selected.map(t),e.deselected.map(t))}},watch:{image:function(e){n.setSource(new ol.source.Canvas({canvas:e,projection:this.projection,canvasExtent:this.extent,canvasSize:[e.width,e.height]}))},annotations:function(e){i.clear(!0),i.addFeatures(this.annotations.map(this.createFeature))},selectedAnnotations:function(e){var t=i,n=this.selectFeatures;n.clear(),e.forEach(function(e){n.push(t.getFeatureById(e.id))})},extent:function(t,n){if(t[2]!==n[2]||t[3]!==n[3]){var i=ol.extent.getCenter(t);this.initialized||(i=this.center||i,this.initialized=!0),e.setView(new ol.View({projection:this.projection,center:i,resolution:this.resolution,zoomFactor:1.5,minResolution:.25,extent:t})),void 0===this.resolution&&e.getView().fit(t,e.getSize())}}},created:function(){var i=this,a=biigle.$require("annotations.stores.styles");e=biigle.$require("annotations.stores.map"),e.addLayer(n),o.setStyle(a.features),e.addLayer(o),biigle.$require("biigle.events").$on("sidebar.toggle",function(){i.$nextTick(function(){e.updateSize()})}),e.on("moveend",function(t){var n=e.getView();i.$emit("moveend",{center:n.getCenter(),resolution:n.getResolution()})}),t=new ol.interaction.Select({style:a.highlight,layers:[o],multi:!0}),e.addInteraction(t),t.on("select",this.handleFeatureSelect)},mounted:function(){e.setTarget(this.$el)}}}),biigle.$component("annotations.components.annotationsFilter",{components:{typeahead:biigle.$require("core.components.typeahead")},props:{annotations:{type:Array,required:!0}},data:function(){return{availableFilters:["label","user","shape","session"],selectedFilter:null,selectedData:null,active:!1}},computed:{placeholder:function(){return this.selectedFilter?this.selectedFilter+" name":"filter annotations"},labelData:function(){var e={},t=[];this.annotations.forEach(function(t){t.labels.forEach(function(t){e[t.label.id]=t.label})});for(var n in e)e.hasOwnProperty(n)&&t.push(e[n]);return t},userData:function(){var e={},t=[];this.annotations.forEach(function(t){t.labels.forEach(function(t){e[t.user.id]=t.user})});for(var n in e)e.hasOwnProperty(n)&&(e[n].name=e[n].firstname+" "+e[n].lastname,t.push(e[n]));return t},shapeData:function(){var e=biigle.$require("annotations.shapes"),t=[];for(var n in e)e.hasOwnProperty(n)&&t.push({id:parseInt(n,10),name:e[n]});return t},sessionData:function(){return biigle.$require("annotations.sessions").map(function(e){return e.starts_at=new Date(e.starts_at),e.ends_at=new Date(e.ends_at),e})},data:function(){return this.selectedFilter?this[this.selectedFilter+"Data"]||[]:[]},selectedDataName:function(){return this.selectedData?this.selectedData.name:""}},methods:{labelFilterFunction:function(e){return function(t){return t.labels.filter(function(t){return t.label.id===e.id}).length>0}},userFilterFunction:function(e){return function(t){return t.labels.filter(function(t){return t.user.id===e.id}).length>0}},shapeFilterFunction:function(e){return function(t){return t.shape_id===e.id}},sessionFilterFunction:function(e){var t={};return e.users.forEach(function(e){t[e.id]=null}),function(n){for(var i=n.labels.length-1;i>=0;i--)if(t.hasOwnProperty(n.labels[i].user.id)){var o=new Date(n.created_at);return o>=e.starts_at&&o<e.ends_at}return!1}},selectData:function(e){this.selectedData=e,this.activateFilter()},activateFilter:function(){this.selectedFilter&&this.selectedData&&(this.active=!0,this.$emit("filter",this[this.selectedFilter+"FilterFunction"](this.selectedData)))},deactivateFilter:function(){this.active=!1,this.selectedData=null,this.$emit("filter",null)}}}),biigle.$component("annotations.components.annotationsTab",{components:{labelItem:biigle.$require("annotations.components.annotationsTabItem"),annotationsFilter:biigle.$require("annotations.components.annotationsFilter")},props:{annotations:{type:Array,required:!0},filteredAnnotations:{type:Array,required:!0}},computed:{items:function(){var e=[],t={};return this.filteredAnnotations.forEach(function(n){n.labels.forEach(function(i){var o={annotation:n,annotationLabel:i};t.hasOwnProperty(i.label.id)?t[i.label.id].push(o):(t[i.label.id]=[o],e.push(i.label))})}),e.map(function(e){return{label:e,annotations:t[e.id]}})}},methods:{reallyScrollIntoView:function(e){var t,n=this.$refs.scrollList,i=n.scrollTop,o=n.offsetHeight,a=1/0,r=0;e.forEach(function(e){for(var i=n.querySelectorAll('[data-annotation-id="'+e.id+'"]'),o=i.length-1;o>=0;o--)t=i[o],a=Math.min(t.offsetTop,a),r=Math.max(t.offsetTop+t.offsetHeight,r)},this),i>a?n.scrollTop=a:i+o<r&&(o>=r-a?n.scrollTop=r-n.offsetHeight:n.scrollTop=a)},scrollIntoView:function(e){0!==e.length&&this.$nextTick(function(){this.reallyScrollIntoView(e)})},keepElementPosition:function(e){var t=this.$refs.scrollList,n=e.offsetTop-t.scrollTop;this.$nextTick(function(){this.$nextTick(function(){var i=e.offsetTop-t.scrollTop;t.scrollTop+=i-n})})},bubbleFilter:function(e){this.$emit("filter",e)}}}),biigle.$component("annotations.components.annotationsTabItem",{components:{annotationItem:biigle.$require("annotations.components.annotationsTabSubItem")},props:{item:{type:Object,required:!0}},data:function(){return{isOpen:!1}},computed:{label:function(){return this.item.label},annotationItems:function(){return this.item.annotations},count:function(){return this.annotationItems.length},hasSelectedAnnotation:function(){for(var e=this.annotationItems,t=e.length-1;t>=0;t--)if(e[t].annotation.selected===!0)return!0;return!1},isSelected:function(){return this.isOpen||this.hasSelectedAnnotation},classObject:function(){return{selected:this.isSelected}},colorStyle:function(){return{"background-color":"#"+this.label.color}},title:function(){return"List all annotations with label "+this.label.name},countTitle:function(){return"There are "+this.count+" annotations with this label"}},methods:{toggleOpen:function(){this.isOpen=!this.isOpen},bubbleSelect:function(e){this.$emit("select",e)}}}),biigle.$component("annotations.components.annotationsTabSubItem",{props:{item:{type:Object,required:!0},userId:{type:Number,required:!0}},computed:{annotation:function(){return this.item.annotation},label:function(){return this.item.annotationLabel},isSelected:function(){return this.annotation.selected},classObject:function(){return{selected:this.isSelected}},shapeClass:function(){return"icon-"+this.annotation.shape.toLowerCase()},username:function(){return this.label.user?this.label.user.firstname+" "+this.label.user.lastname:"(user deleted)"},canBeDetached:function(){return this.label.user&&this.label.user.id===this.userId},events:function(){return biigle.$require("biigle.events")}},methods:{toggleSelect:function(e){this.$emit("select",this.$el),this.isSelected?this.events.$emit("annotations.deselect",this.annotation,e):this.events.$emit("annotations.select",this.annotation,e)},focus:function(){this.events.$emit("annotations.focus",this.annotation)},detach:function(){this.events.$emit("annotations.detach",this.annotation)}}}),biigle.$component("annotations.components.labelIndicator",{props:{label:{required:!0}},computed:{hasLabel:function(){return!!this.label}}}),biigle.$component("annotations.components.labelsTab",{components:{labelTrees:biigle.$require("labelTrees.components.labelTrees")},data:function(){return{labelTrees:biigle.$require("annotations.labelTrees")}},methods:{handleSelectedLabel:function(e){this.$emit("select",e)},handleDeselectedLabel:function(e){this.$emit("select",null)}}}),biigle.$component("annotations.components.minimap",function(){var e=new ol.Map({controls:[],interactions:[]}),t=new ol.source.Vector,n=new ol.Feature;t.addFeature(n);var i,o;return{props:{extent:{type:Array,required:!0},projection:{type:Object,required:!0}},methods:{refreshViewport:function(){n.setGeometry(ol.geom.Polygon.fromExtent(i.calculateExtent(o)))},dragViewport:function(e){i.setCenter(e.coordinate)}},computed:{intendedWidth:function(){return this.$el.clientWidth},intendedHeight:function(){return this.$el.clientHeight}},created:function(){var n=biigle.$require("annotations.stores.map");o=n.getSize(),i=n.getView(),e.addLayer(n.getLayers().item(0)),e.addLayer(new ol.layer.Vector({source:t,style:biigle.$require("annotations.stores.styles").viewport})),n.on("postcompose",this.refreshViewport),n.on("change:size",function(){o=n.getSize()}),n.on("change:view",function(){i=n.getView()}),e.on("pointerdrag",this.dragViewport),e.on("click",this.dragViewport)},watch:{extent:function(t){var n=Math.max(t[2]/this.intendedWidth,t[3]/this.intendedHeight);e.setView(new ol.View({projection:this.projection,center:ol.extent.getCenter(t),resolution:n})),this.$el.style.width=Math.round(t[2]/n)+"px",this.$el.style.height=Math.round(t[3]/n)+"px",e.updateSize()}},mounted:function(){e.setTarget(this.$el)}}}),biigle.$component("annotations.components.sidebar",{mixins:[biigle.$require("core.components.sidebar")],created:function(){}}),biigle.$declare("annotations.ol.ZoomToNativeControl",function(){function e(e){var t=e||{},n=t.label?t.label:"1",i=document.createElement("button"),o=this;i.innerHTML=n,i.title="Zoom to original resolution",i.addEventListener("click",function(){o.zoomToNative.call(o)});var a=document.createElement("div");a.className="zoom-to-native ol-unselectable ol-control",a.appendChild(i),ol.control.Control.call(this,{element:a,target:t.target}),this.duration_=void 0!==t.duration?t.duration:250}return ol.inherits(e,ol.control.Control),e.prototype.zoomToNative=function(){var e=this.getMap(),t=e.getView();if(t){var n=t.getResolution();n&&(this.duration_>0&&e.beforeRender(ol.animation.zoom({resolution:n,duration:this.duration_,easing:ol.easing.easeOut})),t.setResolution(t.constrainResolution(1)))}},e}),biigle.$declare("annotations.stores.annotations",function(){var e=biigle.$require("biigle.events"),t=biigle.$require("api.images");biigle.$require("api.annotations");return new Vue({data:{cache:{}},computed:{imageFileUri:function(){return biigle.$require("annotations.imageFileUri")},shapeMap:function(){return biigle.$require("annotations.shapes")}},methods:{parseAnnotations:function(e){var t=new Vue.Promise(function(t,n){200===e.status?t(e.data):n("Failed to load annotations!")});return t},resolveShapes:function(e){return e.forEach(function(e){e.shape=this.shapeMap[e.shape_id]},this),e},setSelected:function(e){return e.forEach(function(e){e.selected=!1}),e},fetchAnnotations:function(e){return this.cache.hasOwnProperty(e)||(this.cache[e]=t.getAnnotations({id:e}).then(this.parseAnnotations).then(this.resolveShapes)),this.cache[e].then(this.setSelected)},updateCache:function(e,t,n){var i=this;this.fetchAnnotations(e).then(function(){i.fetchAnnotations(n)}).then(function(){i.fetchAnnotations(t)})}},created:function(){e.$on("images.change",this.updateCache)}})}),biigle.$declare("annotations.stores.images",function(){var e=biigle.$require("biigle.events");return new Vue({data:{cache:{},cachedIds:[],maxCacheSize:10},computed:{imageFileUri:function(){return biigle.$require("annotations.imageFileUri")}},methods:{createImage:function(e){var t=document.createElement("img"),n=new Vue.Promise(function(n,i){t.onload=function(){n(this)},t.onerror=function(){i("Failed to load image "+e+"!")}});return t.src=this.imageFileUri.replace("{id}",e),n},drawImage:function(e){var t=document.createElement("canvas");return t.width=e.width,t.height=e.height,t.getContext("2d").drawImage(e,0,0),t},fetchImage:function(e){return this.cache.hasOwnProperty(e)||(this.cache[e]=this.createImage(e),this.cachedIds.push(e)),this.cache[e].then(this.drawImage)},updateCache:function(e,t,n){var i=this;this.fetchImage(e).then(function(){i.fetchImage(n)}).then(function(){i.fetchImage(t)})}},watch:{cachedIds:function(e){if(e.length>this.maxCacheSize){var t=e.shift();this.cache[t];delete this.cache[t]}}},created:function(){e.$on("images.change",this.updateCache)}})}),biigle.$declare("annotations.stores.map",function(){var e=new ol.Map({renderer:"canvas",controls:[new ol.control.Zoom,new ol.control.ZoomToExtent({tipLabel:"Zoom to show whole image",label:""})],interactions:ol.interaction.defaults({altShiftDragRotate:!1,doubleClickZoom:!1,keyboard:!1,shiftDragZoom:!1,pinchRotate:!1,pinchZoom:!1})}),t=biigle.$require("annotations.ol.ZoomToNativeControl");return e.addControl(new t({label:""})),e}),biigle.$declare("annotations.stores.styles",function(){var e={white:[255,255,255,1],blue:[0,153,255,1],orange:"#ff5e00"},t=6,n=3,i=new ol.style.Stroke({color:e.white,width:5}),o=new ol.style.Stroke({color:e.white,width:6}),a=new ol.style.Stroke({color:e.blue,width:n}),r=new ol.style.Stroke({color:e.orange,width:n}),s=new ol.style.Fill({color:e.blue}),l=new ol.style.Fill({color:e.orange}),c=new ol.style.Stroke({color:e.white,width:2}),u=new ol.style.Stroke({color:e.white,width:n}),h=new ol.style.Stroke({color:e.white,width:2,lineDash:[3]}),d=new ol.style.Stroke({color:e.blue,width:n,lineDash:[5]});new ol.style.Fill({color:e.blue}),new ol.style.Fill({color:e.orange});return{colors:e,features:function(e){var n=e.get("color");return n=n?"#"+n:_colors.blue,[new ol.style.Style({stroke:i,image:new ol.style.Circle({radius:t,fill:new ol.style.Fill({color:n}),stroke:c})}),new ol.style.Style({stroke:new ol.style.Stroke({color:n,width:3})})]},highlight:[new ol.style.Style({stroke:o,image:new ol.style.Circle({radius:t,fill:l,stroke:u}),zIndex:200}),new ol.style.Style({stroke:r,zIndex:200})],editing:[new ol.style.Style({stroke:i,image:new ol.style.Circle({radius:t,fill:s,stroke:h})}),new ol.style.Style({stroke:d})],viewport:[new ol.style.Style({stroke:a}),new ol.style.Style({stroke:new ol.style.Stroke({color:e.white,width:1})})]}});