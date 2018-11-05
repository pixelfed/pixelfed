<style>
.carousel {
  position: relative;
  width: 600px;
  height: 400px;
  overflow: hidden;
  margin: 0 auto;
}
.carousel:hover .slide:after,
.carousel:hover .counter,
.carousel:hover .slide:before {
  opacity: 1;
}
.slide {
  float: right;
  position: absolute;
  z-index: 1;
  width: 600px;
  height: 400px;
  background-color: #fff;
  text-align: center;
  transition: opacity 0.4s;
  opacity: 1;
}
.slide:before {
  content: attr(annot);
  display: block;
  position: absolute;
  left: 20px;
  bottom: 20px;
  color: #fff;
  font-size: 14px;
  font-weight: bold;
  z-index: 12;
  opacity: 0;
  transition: opacity 0.3s;
}
.slide:after {
  content: attr(slide);
  display: block;
  position: absolute;
  bottom: 0;
  transition: opacity 0.3s;
  width: 100%;
  height: 80px;
  opacity: 0;
  background-image: linear-gradient(transparent, rgba(0,0,0,0.2));
  text-align: left;
  text-indent: 549px;
  line-height: 101px;
  font-size: 13px;
  color: #fff;
  font-weight: bold;
}
.counter {
  position: absolute;
  bottom: 20px;
  right: 2px;
  height: 20px;
  width: 60px;
  z-index: 2;
  text-align: center;
  color: #fff;
  line-height: 21px;
  font-size: 13px;
  font-weight: bold;
  opacity: 0;
  transition: opacity 0.3s;
}
.carousel-slide {
  top: 0;
  right: 0;
  float: right;
  position: absolute;
  margin-top: 0;
  z-index: 9;
  height: 100%;
  width: 100%;
  opacity: 0;
  cursor: pointer;
}
.carousel-slide:checked {
  z-index: 8;
}
.carousel-slide:checked + .slide {
  opacity: 0;
}
.carousel-slide:checked:nth-child(1):checked {
  z-index: 9;
}
.carousel-slide:nth-child(1):checked {
  float: left;
  z-index: 9;
}
.carousel-slide:nth-child(1):checked + .slide {
  opacity: 1;
}
.carousel-slide:nth-child(1):checked ~ .carousel-slide {
  float: left;
  z-index: 8;
}
.carousel-slide:nth-child(1):checked ~ .carousel-slide + .slide {
  opacity: 0;
}
.carousel-slide:nth-child(1):checked ~ .carousel-slide:checked {
  z-index: 9;
}
.carousel-slide:nth-child(1):checked ~ .carousel-slide:checked + .slide {
  opacity: 1;
}
</style>
<template>
<div class="">
  <div class="postPresenterLoader text-center">
    <div class="lds-ring"><div></div><div></div><div></div><div></div></div> 
  </div>
  <div class="postPresenterContainer d-none">

  </div>
</div>
</template>

<script>

pixelfed.presenter = {
  show: {
    image: function(container, media) {
      let wrapper = $('<div>');
      wrapper.addClass(media[0]['filter_class']);
      let el = $('<img>');
      el.attr('src', media[0]['url']);
      el.attr('title', media[0]['description']);
      wrapper.append(el);
      container.append(wrapper);
    },

    video: function(container, media) {
      let wrapper = $('<div>');
      wrapper.addClass('embed-responsive embed-responsive-4by3');
      let el = $('<video>');
      el.addClass('embed-responsive-item');
      el.attr('controls', '');
      el.attr('src', media[0]['url']);
      el.attr('title', media[0]['description']);
      wrapper.append(el);
      container.append(wrapper);
    },

    imageAlbum: function(container, media) {
      let wrapper = $('<div>');
      wrapper.addClass('carousel');
      let counter = $('<div>');
      counter.attr('class', 'counter');
      counter.attr('count', media.length);
      counter.text('  / ' + media.length);
      for(var i = media.length - 1; i >= 0; i--) {
        let item = media[i];
        let carouselItem = $('<div>').addClass('slide d-flex align-items-center');
        carouselItem.attr('slide', i + 1);
        carouselItem.attr('annot', item.description);
        let check = $('<input>');
        check.attr('type', 'checkbox');
        check.attr('class', 'carousel-slide');
        let img = $('<img>');
        img.addClass('img-fluid');
        img.attr('src', item['url']);
        carouselItem.append(img);
        wrapper.append(check);
        wrapper.append(carouselItem);
        pixelfed.orientation.set(item.orientation);

      }
      wrapper.append(counter);
      container.append(wrapper);
    }
  }
};

export default {
    props: ['status-id', 'status-username', 'status-template']
}
</script>