import 'materialize-css';
import './scss/index.scss';
import statistics from "./js/statistics";
document.addEventListener('DOMContentLoaded', function () {
    M.AutoInit();
    statistics();
});
