<template>
  <k-block-figure
    :caption="content.caption"
    :empty-text="$t('field.blocks.video.placeholder') + ' …'"
    :is-empty="!video"
    empty-icon="video"
    @open="open"
    @update="update"
  >
    <k-aspect-ratio ratio="16/9">
      <iframe v-if="video" :src="video" />
    </k-aspect-ratio>
  </k-block-figure>
</template>

<script>
export default {
  computed: {
    video() {
      return this.$helper.embed.video(this.content.url);
    }
  }
};
</script>
