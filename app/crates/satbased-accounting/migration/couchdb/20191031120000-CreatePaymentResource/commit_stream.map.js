function (commit) {
    if (/^satbased\.accounting\.payment\-/.test(commit._id)) {
        count = commit.eventLog.length;
        emit([ commit.aggregateId, commit.eventLog[count-1].revision ], 1);
    }
}