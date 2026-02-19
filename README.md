# Smart Resume (local_smartresume)

A Moodle local plugin that enhances the user experience by automatically identifying and guiding students to their next incomplete activity within a course.

## Features

- **Automatic Detection:** Scans the current course for activities with completion tracking enabled.
- **Smart Navigation:** Identifies the *first* visible activity that the current user has not yet completed.
- **Visual Focus:** (Via included JavaScript) Highlights or scrolls to the next activity, helping students quickly resume their learning path.
- **Configurable:** Can be enabled or disabled globally via Site Administration.

## Installation

1.  Clone this repository into your Moodle's `local/` directory:
    ```bash
    git clone https://github.com/hetcxp/moodle_smartresume.git local/smartresume
    ```
2.  Run the Moodle upgrade script (via command line or web interface):
    ```bash
    php admin/cli/upgrade.php
    ```

## Configuration

Go to **Site administration > Plugins > Local plugins > Smart Resume**.

-   **Enable Smart Resume:** Toggle the functionality on or off globally.

## Requirements

-   Moodle 4.0 or higher (uses the new Hook API).
-   Completion tracking must be enabled at the site and course level.

## License

This plugin is licensed under the [GPLv3](http://www.gnu.org/copyleft/gpl.html).
