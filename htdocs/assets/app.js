import 'materialize-css';
import './scss/index.scss';
import statistics from "./js/statistics";
import searchForm from "./js/searchForm";
document.addEventListener('DOMContentLoaded', function () {
    M.AutoInit();
    statistics();
    searchForm();
});
