import init from "./App";

// @ts-expect-error
const $ = jQuery;

$(document).ready(() => {
  console.log("ready");
  $(document.body).on("updated_checkout", (a) => {
    const el = document.getElementById("kujira-usk-checkout");
    el && init(el, el.dataset);
  });
});
