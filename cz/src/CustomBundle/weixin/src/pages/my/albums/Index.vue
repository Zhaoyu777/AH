<template>
  <main class="app-main bg-white">
    <div v-if="albums.length > 0">
      <div v-for="album in albums" class="wall-group">
      <div class="albums-day" >{{ album.day }}</div>
      <div class="albums-month">{{ album.month }}</div>
      <img :src="host + album.uri" class="albums-picture" alt="">
      <div class='albums-icon'>
          <span >
            <i class="cz-icon cz-icon-favoriteoutline"></i> 
            <span class="albums-font">{{ album.likeNum}}</span>
          </span>
          <span class="albums-icon-remark">
            <i class="cz-icon cz-icon-forum"></i> 
            <span class="albums-font">{{ album.postNum }}</span>
          </span>
      </div>
      </div>
    </div>
    <div class="no-picture__tips" v-else>
      <p>请积极参与课堂互动、上传照片哦</p>
    </div>
  </main>
</template>

<script>
import api from '@/assets/js/api';

export default {
  data() {
    return {
      albums: null,
      host: this.$getCookie('host'),
    }
  },
  created() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      this.$isLoading();
      this.$http.get(api.my.albums()).then((response) => {
        this.$endLoading();
        this.albums = response.data;
      }, (response) => {
        this.$endLoading();
        this.$ajaxError();
      });
    }
  }
}
</script>

<style lang="less" scoped>
.albums-picture {
  text-align: center;
  border-radius:.25rem .25rem .25rem .25rem;
  width: 13.4375rem;
  height: 120.7.53125rem;
  margin: 0rem 1.875rem;
}

.wall-group {
  border-radius: 0.625rem;
  margin: 1.875rem .75rem;
  padding: .3125rem;
  line-height: 1.875rem;
}

.albums-day {
  float: left;
  font-size: 1.5rem;
}

.albums-month {
  float: left;
  margin: 0.2rem 0.275rem;
  font-size: .75rem;
}

.albums-icon {
  margin: 0 5.3125rem;
  color: #919191;
}

.cz-icon {
  font-family: Material-Design-Iconic-Font;
  font-size: 1.0625rem;
  color: #919191;
  letter-spacing: 0px;
}

.albums-icon-remark {
  margin: 0 .75rem;
}

.albums-font {
  font-size: .9375rem;
  margin: 0 .375rem;
}

.no-picture__tips {
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  margin-top: -0.4375rem;
  font-size: 1.125rem;
  color: #919191;
  text-align: center;
}
</style>

