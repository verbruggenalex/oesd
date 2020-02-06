<?php
// phpcs:ignoreFile Drupal,DrupalPractice

namespace OpenEuropa\Distribution\TaskRunner\Commands;

use Lurker\Event\FilesystemEvent;
use NuvoleWeb\Robo\Task as NuvoleWebTasks;
use OpenEuropa\TaskRunner\Commands\AbstractCommands;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Yaml\Yaml;

/**
 * Class BuildProfileCommands.
 *
 * @package OpenEuropa\Distribution\TaskRunner\Commands
 */
class BuildProfileCommands extends AbstractCommands {

  use NuvoleWebTasks\Config\Php\loadTasks;

  /**
  * {@inheritdoc}
  */
  public function getConfigurationFile() {
    return __DIR__ . '/../../../runner.yml.dist';
  }

  /**
   * Build a profile.
   *
   * @param array $options
   *   Additional options for the command.
   *
   * @command oe-distribution:build-profile
   *
   * @option profile-name The name of the profile.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   The collection builder.
   */
  public function buildProfile(array $options = [
    'profile-name' => InputOption::VALUE_REQUIRED,
  ]) {
    $tasks = [];
    $config = $this->getConfig();
    $profileName = $options['profile-name'];
    $install = $config->get("profiles.$profileName.install");
    $dependencies = $config->get("profiles.$profileName.dependencies");
    $modules = array_merge($install, $dependencies);

    $tasks[] = $this->taskExecStack()
        ->stopOnFail()
        ->exec('rm -rf config/sync')
        ->exec('./vendor/bin/drush sql-drop -y')
        ->exec('./vendor/bin/run dsi --site-profile=minimal --no-interaction');
    
    foreach ($modules as $module) {
      $tasks[] = $this->taskExec('./vendor/bin/drush en -y ' . $module);
    }
    
    if (in_array('oe_multilingual', $modules)) {
      $tasks[] = $this->taskExec('./vendor/bin/drush oe-multilingual:import-local-translations');
      
      if ($profileName === 'oe_profile_complete') {
        // If we have installed the complete profile we copy the translations so
        // we can ship them with the profile. Each profile will symlink to this
        // folder on config export.
        // @TODO: We need to upload this as an asset instead of committing it.
        $tasks[] = $this->taskExecStack()
            ->stopOnFail()
            ->exec('./vendor/bin/drush locale:update')
            ->exec('rm -rf translations/*')
            ->exec("cp -Rf web/sites/default/files/translations translations");
      }
    }

    $tasks[] = $this->taskExecStack()
        ->stopOnFail()
        ->exec('./vendor/bin/drush cr')
        ->exec('./vendor/bin/drush theme:enable oe_theme -y')
        ->exec('./vendor/bin/drush config-set system.theme default oe_theme -y')
        ->exec('./vendor/bin/drush theme:enable seven -y')
        ->exec('./vendor/bin/drush config-set system.theme admin seven -y');
        // Not possible because stark is a dependency of the minimal profile.
        // We will just delete the config files after exporting configuration.
        // ->exec('./vendor/bin/drush theme:uninstall stark -y');

    // Build and return task collection.
    return $this->collectionBuilder()->addTaskList($tasks);
  }

  /**
   * Export a profile.
   *
   * @param array $options
   *   Additional options for the command.
   *
   * @command oe-distribution:export-profile
   *
   * @option profile-name The name of the profile.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   The collection builder.
   */
  public function exportProfile(array $options = [
    'profile-name' => InputOption::VALUE_REQUIRED,
  ]) {

    $tasks = [];
    $config = $this->getConfig();
    $configFolder = 'config/sync';
    $profileName = $options['profile-name'];
    $dependencies = $config->get("profiles.$profileName.dependencies");
    $infoFile = "profiles/$profileName/$profileName.info.yml";
    $profileFolder = "profiles/$profileName/config/install";

    // @TODO: Copy or Rsynce the sites/default/files/translations folder to the
    // correct folder in the translations/<language-name>. <- done in
    // oe-distribution:build-profile
    $this->taskExecStack()
        ->stopOnFail()
        ->exec("rm -rf $configFolder")
        ->exec("rm -rf $profileFolder")
        ->exec("mkdir -p $profileFolder")
        ->exec('./vendor/bin/drush config:export -y')
        ->exec("rsync -az $configFolder/ $profileFolder --delete")->run();

    $coreExtension = Yaml::parseFile("$profileFolder/core.extension.yml");
    if (isset($coreExtension['module'])) {
      $modules = array_keys($coreExtension['module']);
      $install = array_diff($modules, $dependencies, ['minimal']);
      $info = Yaml::parseFile($infoFile);
      $info['install'] = $install;
      $info['dependencies'] = $dependencies;
      // Dumps keys when array is bigger than a certain amount (like 15 or so).
      $yaml = Yaml::dump($info, 2, 4);
      file_put_contents($infoFile, $yaml);
    }
    
    // @TODO: Parse the system.theme.yml file and put the default and admin
    // theme in the profile info file.

    // Remove files and lines we don't need.
    $this->taskExecStack()
        ->stopOnFail()
        ->exec("rm -rf $profileFolder/system.site.yml $profileFolder/core.extension.yml")
        ->exec("rm -rf $profileFolder/block.block.stark_*.yml")
        ->exec("rm -rf $profileFolder/block.block.seven_*.yml")
        ->exec("cd $profileFolder && ln -sf ../../../../translations translations")
        ->exec("find ./$profileFolder -type f -exec sed -i -e '/_core:/,+1d' {} \\;")
        ->exec("find ./$profileFolder -type f -exec sed -i -e '/^uuid: /d' {} \\;")
        ->exec("rm -rf $configFolder")->run();

    // // Build and return task collection.
    // return $this->collectionBuilder()->addTaskList($tasks);
  }

}
