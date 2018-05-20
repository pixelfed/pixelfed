window.ls = {};

window.ls.get = function(key) {
  return JSON.parse(localStorage.getItem(key));
}


window.ls.set = function(key, val) {
  try {
    localStorage.setItem(key, JSON.stringify(val));
    return true;
  } catch(e) {
    return false;
  }
}

window.ls.del = function(key) {
  try {
    localStorage.removeItem(key);
    return true;
  } catch(e) {
    return false;
  }
}

window.ls.clear = function() {
  try {
    localStorage.clear();
    return true;
  } catch(e) {
    return false;
  }
}