<template>
  <div id="app" class="font-inter">
    <!-- global loader -->
    <Loader v-if="isLoading"/>

    <!-- global alert -->
    <Toast/>
      
    <!-- if not login page -->
    <GlobalLayout v-if="!isAuthPage">
      <router-view/>
    </GlobalLayout>

    <!-- if login page -->
     <router-view v-else/>
  </div>
</template>

<script>
// import Alert from './components/Alert.vue';
import GlobalLayout from './components/layouts/GlobalLayout.vue'
import Loader from './components/Loader.vue'
import Toast from 'primevue/toast'

export default {
  name: 'App',
  components: {
    GlobalLayout,
    Loader,
    Toast,
    // Alert
  },
  data() {
    return {
      isLoading: false,
    }
  },
  computed: {
    isAuthPage() {
      return this.$route.path === '/' || this.$route.path === '/login'
    }
  },
  provide() {
    return {
      showLoader: this.showLoader,
      hideLoader: this.hideLoader,
    }
  },
  methods: {
    showLoader() {
      this.isLoading = true
    },
    hideLoader() {
      this.isLoading = false
    },
  },
}
</script>

<style>
html,
body {
  margin: 0;
  padding: 0;
  height: 100%;
}

#app {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

main {
  flex: 1;
  overflow: hidden;
}
</style>