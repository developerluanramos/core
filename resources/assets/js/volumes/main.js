import './export';
import AnnotationSessionPanel from './annotationSessionPanel';
import CreateFormStep1 from './createFormStep1';
import CreateFormStep2 from './createFormStep2';
import CreateFormStep3 from './createFormStep3';
import CreateFormStep4 from './createFormStep4';
import CreateFormStep5 from './createFormStep5';
import CreateFormStep6 from './createFormStep6';
import CloneForm from './cloneForm';
import FileCount from './fileCount';
import FilePanel from './filePanel';
import MetadataUpload from './metadataUpload';
import ProjectsBreadcrumb from './projectsBreadcrumb';
import SearchResults from './searchResults';
import VolumeContainer from './volumeContainer';

biigle.$mount('annotation-session-panel', AnnotationSessionPanel);
biigle.$mount('create-volume-form-step-1', CreateFormStep1);
biigle.$mount('create-volume-form-step-2', CreateFormStep2);
biigle.$mount('create-volume-form-step-3', CreateFormStep3);
biigle.$mount('create-volume-form-step-4', CreateFormStep4);
biigle.$mount('create-volume-form-step-5', CreateFormStep5);
biigle.$mount('create-volume-form-step-6', CreateFormStep6);
biigle.$mount('clone-volume-form', CloneForm);
biigle.$mount('file-panel', FilePanel);
biigle.$mount('projects-breadcrumb', ProjectsBreadcrumb);
biigle.$mount('search-results', SearchResults);
biigle.$mount('volume-container', VolumeContainer);
biigle.$mount('volume-file-count', FileCount);
biigle.$mount('volume-metadata-upload', MetadataUpload);
