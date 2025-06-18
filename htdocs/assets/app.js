import 'materialize-css';
import './scss/index.scss';
import statistics from "./js/statistics";
import searchFormUI from "./js/searchFormUI";
import {searchFormSubmit} from "./js/searchFormSubmit";
import specimenMap, {dynamicReferences} from "./js/specimenDetailMap";
import materializeInit from "./js/materializeInit";
import specimenLinks from "./js/specimenDetailLinks";
document.addEventListener('DOMContentLoaded', function () {

    statistics();
    searchFormUI();
    searchFormSubmit();
    specimenMap();
    specimenLinks();
    dynamicReferences();
    materializeInit();
});
