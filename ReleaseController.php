<?php
namespace execut\release;


use yii\console\Controller;
use DirectoryIterator;
use yii\base\Exception;

/**
 * ReleaseController
 *
 * @author Yuriy Mamaev
 */
class ReleaseController extends Controller
{
    public $level = 2;
    public $message = null;
    public $vendorFolder = null;
    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['level', 'message',]
        );
    }

    /**
     * @inheritdoc
     * @since 2.0.8
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'l' => 'level',
            'm' => 'message',
        ]);
    }

    public function actionIndex() {
        $this->checkRequiredParams();
        $folderNames = $this->vendorFolder;
        if (is_string($folderNames)) {
            $folderNames = [$folderNames];
        }

        foreach ($folderNames as $folderName) {
            $folderPath = \yii::getAlias('@vendor/' . $folderName);
            $dirIterator = new DirectoryIterator($folderPath);
            $isShowContinue = false;
            foreach ($dirIterator as $file) {
                if (!$file->isDot() && $file->isDir() && file_exists($file->getPathname() . '/.git')) {
                    $folder = $file->getPathname();
                    chdir($folder);
                    $resultArray = [];
                    exec('git status', $resultArray);
                    $result = implode("\n", $resultArray);
                    if (strpos($result, 'nothing to commit, working') !== false) {
                        if (!$this->runCommands([
                            'git checkout master',
                            'git pull',
                        ])) {
                            return;
                        }
                    } else {
                        echo 'Package ' . $file->getFilename() . ' is changed.' . "\n";
                        $commands = [
                            'git add .',
                            'git pull origin master',
                            'git checkout master',
                            'git pull',
                        ];
                        if (!$this->runCommands($commands)) {
                            return;
                        }

                        $resultArray = [];
                        exec('git diff HEAD', $resultArray);
                        echo implode("\n", $resultArray) . "\n";

                        if ($this->message === null) {
                            echo 'Enter commit message ("Fixed bugs" default): ';
                            $message = trim(fgets(STDIN));
                            if (empty($message)) {
                                $message = 'fix';
                                echo $message . "\n";
                            }
                        } else {
                            $message = $this->message;
                        }

                        if ($this->level === null) {
                            echo 'Enter version level, 2 - bugfix, 1 - minor, 0 - major (2 default): ';
                            $level = trim(fgets(STDIN));
                            if (!$level) {
                                $level = 2;
                                echo $level . "\n";
                            }
                        }  else {
                            $level = $this->level;
                        }

                        $level = (int) $level;
                        if ($level > 2 || $level < 0) {
                            throw new Exception('Level can only be 0, 1, 2. Passed level: ' . $level);
                        }

                        $nextVersion = $this->getNextVersion($level);

                        $commands = [
                            'git commit -m \'' . $message . '\'',
                            'git push',
                            'git tag ' . $nextVersion,
                            'git push --tags',
                        ];

                        foreach ($commands as $command) {
                            system($command, $result);
                            if ($result !== 0) {
                                return;
                            }
                        }
                    }
                }
            }
        }
    }

    protected function getNextVersion($level) {
        exec('git tag -l', $out);
        uasort($out, function ($a, $b) {
            $aParts = explode('.', $a);
            $bParts = explode('.', $b);
            foreach ($aParts as $key => $aPart) {
//                if (empty($bParts[$key])) {
//                    return true;
//                }

                if ($aPart > $bParts[$key]) {
                    return true;
                }
                if ($bParts[$key] > $aPart) {
                    return false;
                }
            }
        });

        $currentVersion = end($out);
        if (empty($currentVersion)) {
            $currentVersion = '0.0.1';
        }

        $parts = explode('.', $currentVersion);
        if ($level >= count($parts)) {
            throw new \Exception('Wrong level');
        }

        $parts[$level]++;
        for ($key = $level + 1; $key < count($parts); $key++) {
            $parts[$key] = 0;
        }

        $nextVersion = implode('.', $parts);

        return $nextVersion;
    }

    /**
     * @param $commands
     *
     * @return array
     */
    protected function runCommands($commands)
    {
        foreach ($commands as $command) {
            system($command, $result);
            if ($result !== 0) {
                return false;
            }
        }

        return true;
    }

    protected function checkRequiredParams() {
        $params = ['vendorFolder'];
        foreach ($params as $param) {
            if (empty($this->$param)) {
                throw new Exception('Parameter ' . $param . ' is required');
            }
        }

        return $params;
    }
}