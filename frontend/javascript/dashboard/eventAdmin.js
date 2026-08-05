let box = document.getElementById("box");
let down = false;

function toggleNotifi() {
  if (down) {
    box.style.height = "0px";
    // box.style.opacity = 0;
    box.style.display = "none";
    down = false;
  } else {
    box.style.height = "auto";
    // box.style.opacity = 1;
    box.style.display = "block";
    down = true;
  }
}
