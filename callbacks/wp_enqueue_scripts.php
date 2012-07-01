<?



function compile_sass_file($sass_src, $output_path)
{
  if (!file_exists($sass_src)) wax_error("File $src does not exist for SASS processing.");
  $sass = eval_php($sass_src, array(), true);
  $md5 = md5($sass);

  $dst = $output_path."/$md5.".folderize(ftov($sass_src)).'.css';

  //if(file_exists($dst)) return $dst;
  
  file_put_contents($dst, $sass);

  $renderer = SassRenderer::EXPANDED;
  $parser = new SassParser($output_path, $output_path , $renderer);
  
  // OUTPUT
  
  $css = $parser->fetch($dst, $renderer);

  if (strlen(trim($css))==0) wax_error("$dst failed to output any CSS");
  
  $pfx = dirname(ftov($sass_src));
  $func = "
      if (!startswith(\$matches[1], '/'))
      {
        \$path = '$pfx/'.\$matches[1];
        return 'url('.\$path.')';
      } else {
        \$path = \$matches[1];
      }
      return 'url('.\$path.')';
    ";
  $css = preg_replace_callback("/url\\(['\"]?(.+?)['\"]?\\)/", create_function( '$matches', $func ), $css);
  ensure_writable_folder($output_path);
  if (!file_exists($output_path)) wax_error("Expected output path $output_path for SASS processing.");
  file_put_contents($dst, $css);
  return $dst;
}

foreach(Wax::$modules as $slug=>$m)
{
  $root_fpath = $m->fpath."/assets";
  $fpath = $root_fpath."/sass";
  if (!file_exists($fpath)) continue;
  
  foreach(wax_glob($fpath."/*") as $fname)
  {
    if(!is_file($fname)) continue;
    $fname = compile_sass_file($fname, $this->cache_fpath);
    $parts = pathinfo($fname);
    $key = "wax.sass.{$slug}.{$parts['filename']}";
    $file = basename($fname);
    $url = $this->cache_url;
    wp_deregister_style( $key );
    wp_register_style( $key, "{$url}/$file");
    wp_enqueue_style( $key );
  }
}
