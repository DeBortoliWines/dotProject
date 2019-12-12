#
# $Id$
#
# DO NOT USE THIS SCRIPT DIRECTLY - USE THE INSTALLER INSTEAD.
#
# All entries must be date stamped in the correct format.
#

# 20191211
ALTER TABLE `%dbprefix%tasks` MODIFY task_description LONGTEXT;
ALTER TABLE `%dbprefix%task_log` MODIFY task_log_description LONGTEXT;
ALTER TABLE `%dbprefix%projects` MODIFY project_description LONGTEXT;
ALTER TABLE `%dbprefix%companies` MODIFY company_description LONGTEXT;