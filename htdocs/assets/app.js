import 'materialize-css';
import './scss/index.scss';
import statistics from "./js/statistics";
import searchFormUI from "./js/searchFormUI";
import {searchFormSubmit} from "./js/searchFormSubmit";
import specimenMap, {dynamicReferences} from "./js/specimenDetailMap";
import materializeInit from "./js/materializeInit";
import specimenLinks from "./js/specimenDetailLinks";
import institutionMap from "./js/institutionMap";
document.addEventListener('DOMContentLoaded', function () {

    statistics();
    searchFormUI();
    searchFormSubmit();
    specimenMap();
    // specimenLinks();
    dynamicReferences();
    materializeInit();
    institutionMap();

    const miradorEl = document.getElementById("mirador");
    if (miradorEl) {
        import('./js/specimenDetailMirador')
            .then(({ default: initMirador }) => {
                initMirador(miradorEl);
            })
            .catch(err => {
                console.error('Uanble load Mirador:', err);
            });
    }
});
