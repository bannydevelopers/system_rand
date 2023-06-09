/*!

=========================================================
* Soft UI Dashboard Tailwind - v1.0.4
=========================================================

* Product Page: https://www.creative-tim.com/product/soft-ui-dashboard-tailwind
* Copyright 2022 Creative Tim (https://www.creative-tim.com)
* Licensed under MIT (site.license)

* Coded by www.creative-tim.com

=========================================================

* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

*/
var page = window.location.pathname.split("/").pop().split(".")[0];
var aux = window.location.pathname.split("/");
var to_build = (aux.includes('pages')?'../':'./');
var root = window.location.pathname.split("/")
if (!aux.includes("pages")) {
  page = "dashboard";
}

loadStylesheet(to_build + "css/perfect-scrollbar.css");
loadJS(to_build + "js/perfect-scrollbar.js", true);

loadJS(to_build + "js/plugins/flowbite.min.js", true);

if (document.querySelector("nav [navbar-trigger]")) {
  loadJS(to_build + "js/navbar-collapse.js", true);
}

if (document.querySelector("[data-target='tooltip']")) {
  loadJS(to_build + "js/tooltips.js", true);
  loadStylesheet(to_build + "css/tooltips.css");
}

if (document.querySelector("[nav-pills]")) {
  loadJS(to_build + "js/nav-pills.js", true);
}

if (document.querySelector("[dropdown-trigger]")) {
  loadJS(to_build + "js/dropdown.js", true);

}

if (document.querySelector("[fixed-plugin]")) {
  loadJS(to_build + "js/fixed-plugin.js", true);
}

if (document.querySelector("[navbar-main]")) {
  loadJS(to_build + "js/sidenav-burger.js", true);
  loadJS(to_build + "js/navbar-sticky.js", true);
}

if (document.querySelector("canvas")) {
  // loadJS(to_build + "js/chart-1.js", true);
  // loadJS(to_build + "js/chart-2.js", true);

}
// if (document.querySelector("#dTable")) {
//   loadJS("https://code.jquery.com/jquery-3.5.1.js", false);
//   loadJS("https://cdn.datatables.net/1.13.4/js/jquery.dataTables.js", false);
//   $(document).ready( function () {
//     $('#dTable').DataTable();
//   } );
// }

if (document.querySelector("#message") || document.querySelector(".msg")) {
  loadJS(to_build + "js/plugins/alpine.min.js", true);
}

function loadJS(FILE_URL, async) {
  let dynamicScript = document.createElement("script");

  dynamicScript.setAttribute("src", FILE_URL);
  dynamicScript.setAttribute("type", "text/javascript");
  dynamicScript.setAttribute("async", async);

  document.head.appendChild(dynamicScript);
}

function loadStylesheet(FILE_URL) {
  let dynamicStylesheet = document.createElement("link");

  dynamicStylesheet.setAttribute("href", FILE_URL);
  dynamicStylesheet.setAttribute("type", "text/css");
  dynamicStylesheet.setAttribute("rel", "stylesheet");

  document.head.appendChild(dynamicStylesheet);
}
