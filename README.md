# ANGIE – Akeeba Next Generation Installer

This repository contains the ANGIE installers for Akeeba Backup / Akeeba Solo.

## Overview

All installers are based on the common ANGIE framework and core application found inside the `installation` directory.
Each installer also comes with a platform directory that contains the files of one of the `platforms` subdirectories.
This is the platform-specific part that makes each installer able to reconfigure a particular CMS / script. The INI
files in the main `angie` directory are used by Akeeba Backup / Akeeba Solo to figure out how to include each installer
in the backup archive and which information they should collect.

## License

GNU General Public License version 3 or, at your option, any later version published by the FSF. See `LICENSE` for more
information.
