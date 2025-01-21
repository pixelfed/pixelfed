<template>
  <div class="fullscreen-carousel">
    <div class="glide" ref="glide">
      <div class="glide__track" data-glide-el="track">
        <ul class="glide__slides">
          <li class="glide__slide" v-for="(item, index) in feed" :key="index">
            <div class="slide-content">
              <img :src="item.media_url" :alt="item.caption" class="slide-image" loading="lazy">
              <div v-if="withOverlay" class="slide-overlay">
                <p v-if="withLinks" class="slide-username"><a :href="item.account.url">{{ webfinger }}</a></p>
                <p v-else class="slide-username">{{ webfinger }}</p>
                <div class="d-flex gap-1">
                    <div v-if="withLinks" class="slide-date">
                        <a :href="item.url" target="_blank">{{ formatDate(item.created_at) }}</a>
                    </div>
                    <div v-else class="slide-date">{{ formatDate(item.created_at) }}</div>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
      
      <div class="glide__arrows" data-glide-el="controls">
        <button class="glide__arrow glide__arrow--left fancy-arrow" data-glide-dir="<">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>
        <button class="glide__arrow glide__arrow--right fancy-arrow" data-glide-dir=">">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import Glide from '@glidejs/glide'

export default {
  props: {
    feed: {
      type: Array,
      required: true
    },
    canLoadMore: {
      type: Boolean,
      default: false
    },
    withLinks: {
        type: Boolean,
        default: false
    },
    withOverlay: {
        type: Boolean,
        default: true
    },
    autoPlay: {
        type: Boolean,
        default: false
    },
    autoPlayInterval: {
        type: Number,
        default: () => { return 5000; }
    }
  },
  
  data() {
    return {
      glideInstance: null
    }
  },
  
  mounted() {
    this.initGlide()
  },

  computed: {
    webfinger: {
        get() {
            if(this.feed && this.feed.length) {
                const account = this.feed[0].account
                const domain = new URL(account.url).host
                return `@${account.username}@${domain}`
            }
            return ""
        }
    }
  },
  
    methods: {
        initGlide() {
            this.glideInstance = new Glide(this.$refs.glide, {
                type: 'carousel',
                startAt: 0,
                perView: 1,
                gap: 0,
                hoverpause: false,
                autoplay: this.autoPlay ? this.autoPlayInterval : false,
                keyboard: true
            })

            this.glideInstance.on('run.after', this.checkForPagination)
            this.glideInstance.mount()
        },
    
        checkForPagination() {
            const currentIndex = this.glideInstance.index
            if (currentIndex === this.feed.length - 1 && this.canLoadMore) {
                this.$emit('load-more')
            }
        },
    
    loadMore() {
      this.$emit('load-more')
    },

    formatDate(dateInput, locale = navigator.language) {
        let date;

        if (typeof dateInput === 'string') {
            date = new Date(dateInput);
            if (isNaN(date.getTime())) {
                throw new Error('Invalid date string. Please provide a valid ISO 8601 format.');
            }
        } else if (dateInput instanceof Date) {
            date = dateInput;
        } else {
            throw new Error('Invalid input. Please provide a Date object or an ISO 8601 string.');
        }

        const options = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
        };

        return new Intl.DateTimeFormat(locale, options).format(date);
    },
    
    updateGlide() {
      this.$nextTick(() => {
        if (this.glideInstance) {
          this.glideInstance.update()
        }
      })
    }
  },
  
  watch: {
    feed() {
      this.updateGlide()
    }
  }
}
</script>

<style scoped lang="scss">
.fullscreen-carousel {
  height: 100dvh;
  width: 100dvw;
  position: relative;
  overflow: hidden;
  z-index: 2;
  background: #000;
}

.glide, .glide__track, .glide__slides, .glide__slide {
  height: 100%;
}

.slide-content {
  position: relative;
  height: 100%;
  width: 100%;
}

.slide-image {
  object-fit: contain;
  width: 100%;
  height: 100%;
}

.slide-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.5);
  color: white;
  padding: 8px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.gap-1 {
    gap: 2rem;
}

.slide-image {
    .slide-overlay {
        &:not(:hover) {
            height: 0;
            opacity: 0;
            transform: height 1s ease;
        }
    }
}

.slide-username {
  margin: 0;
  user-select: all;
  font-size: 14px;

  a {
    color: white;
    font-weight: 500;
  }
}

.slide-caption {
  margin: 0;
  font-size: 14px;
}

.slide-date {
  margin: 0;
  font-size: 14px;

    a {
        color: white;
        font-weight: bold;
        text-decoration: none;
    }
}

.glide__arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.5);
  border: none;
  font-size: 24px;
  padding: 10px;
  cursor: pointer;
}

.fancy-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  transition: all 0.3s ease;
  overflow: hidden;
}

.fancy-arrow:hover {
  background: rgba(255, 255, 255, 0.4);
  box-shadow: 0 0 15px rgba(255, 255, 255, 0.5);
}

.fancy-arrow:focus {
  outline: none;
}

.fancy-arrow svg {
  width: 24px;
  height: 24px;
  color: white;
  transition: all 0.3s ease;
}

.fancy-arrow:hover svg {
  transform: scale(1.2);
}

.glide__arrow--left {
  left: 20px;
}

.glide__arrow--right {
  right: 20px;
}

@keyframes pulse {
  0% {
    transform: translateY(-50%) scale(1);
  }
  50% {
    transform: translateY(-50%) scale(1.05);
  }
  100% {
    transform: translateY(-50%) scale(1);
  }
}

.fancy-arrow:active {
  animation: pulse 0.3s ease-in-out;
}

@media (max-width: 768px) {
  .fancy-arrow {
    width: 40px;
    height: 40px;
  }

  .fancy-arrow svg {
    width: 20px;
    height: 20px;
  }

  .glide__arrow--left {
    left: 10px;
  }

  .glide__arrow--right {
    right: 10px;
  }
}
</style>
