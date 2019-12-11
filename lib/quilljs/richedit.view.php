<div style="background-color: #fff; border: 1px solid #000;">
  <div id="<?php echo $this->id ?>-editor-container"><?php echo $this->content ?></div>
</div>
<textarea name="<?php echo $this->id; ?>" style="display:none;"></textarea>
<script>
var quill = new Quill('#<?php echo $this->id ?>-editor-container', {
  modules: {
    toolbar: [
      [{ 'font': [] }],
      [{ 'size': [] }],
      [{ 'align': [] }],
      ['bold', 'italic', 'underline', 'strike'],
      [{ 'header': 1 }, { 'header': 2 }],  
      [{ 'list': 'ordered'}, { 'list': 'bullet' }],
      ['link', 'image'],
      ['clean'] 
    ]
  },
  placeholder: '',
  theme: 'snow'
});
</script>
