import 'materialize-css';
import './scss/index.scss';
import statistics from "./js/statistics";
import searchFormUI from "./js/searchFormUI";
import {searchFormSubmit} from "./js/searchFormSubmit";
document.addEventListener('DOMContentLoaded', function () {
    M.AutoInit();
    statistics();
    searchFormUI();
    searchFormSubmit()
});
