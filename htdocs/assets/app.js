import 'materialize-css';
import './scss/index.scss';
import statistics from "./js/statistics";
import searchFormUI from "./js/searchFormUI";
import {searchFormSubmit} from "./js/searchFormSubmit";
import specimenMap, {dynamicReferences} from "./js/specimenDetail";
import materializeInit from "./js/materializeInit";
document.addEventListener('DOMContentLoaded', function () {

    statistics();
    searchFormUI();
    searchFormSubmit();
    specimenMap();
    dynamicReferences();
    materializeInit();
});
